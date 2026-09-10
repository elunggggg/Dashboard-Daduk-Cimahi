<?php

namespace App\Http\Controllers;

use App\Exports\DataAgregatDkbExport;
use App\Exports\DataAgregatExport;
use App\Models\Laporan;
use App\Models\PengaturanExport;
use App\Services\AuditLogService;
use App\Services\FilterWilayahService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Ekspor Excel/PDF — bisa dipakai Petugas maupun Publik (tanpa login), form
 * dan riwayatnya ada di Dashboard Publik (lihat DashboardPublikController).
 */
class EksporController extends Controller
{
    public function __construct(
        private readonly FilterWilayahService $filter,
        private readonly AuditLogService $audit,
    ) {
    }

    public function unduh(Request $request): BinaryFileResponse|RedirectResponse|Response
    {
        $data = $request->validate([
            'format'          => ['required', 'in:excel,pdf'],
            'waktu_id'        => ['nullable', 'integer', 'exists:dim_waktu,id'],
            'kecamatan'       => ['nullable', 'string', 'max:100'],
            'jenis_indikator' => ['nullable', 'string', 'max:50'],
        ], [], [
            'waktu_id'        => 'periode',
            'jenis_indikator' => 'indikator',
        ]);

        $waktuId   = $data['waktu_id'] ?? null;
        $kecamatan = ($data['kecamatan'] ?? null) ?: null;
        $indikator = ($data['jenis_indikator'] ?? null) ?: null;

        $pengaturan = PengaturanExport::current();

        $query = DataAgregatExport::query($waktuId, $kecamatan, $indikator);
        $total = (clone $query)->count();

        if ($total === 0) {
            return back()->with('error', 'Tidak ada data yang cocok dengan filter — tidak ada yang diunduh.');
        }

        // Diperiksa sebelum apa pun dicatat — ekspor yang ditolak tidak boleh
        // muncul di riwayat laporan seolah-olah berhasil. Batas dari Konfigurasi Export.
        if ($data['format'] === 'pdf' && $total > $pengaturan->batas_baris_pdf) {
            return back()->with('error',
                "Hasil filter {$total} baris, melebihi batas {$pengaturan->batas_baris_pdf} baris untuk PDF. "
                .'Persempit filter atau gunakan format Excel.');
        }

        $judul = $this->susunJudul($waktuId, $kecamatan, $indikator);
        $stamp = now()->format('Ymd-His');

        $this->catat($request, $judul, $data['format'], [
            'waktu_id' => $waktuId, 'kecamatan' => $kecamatan,
            'jenis_indikator' => $indikator, 'jumlah_baris' => $total,
        ]);

        if ($data['format'] === 'excel') {
            // Excel = bergaya DKB: satu sheet per indikator (tata letak pivot
            // wilayah × kategori). Bisa lama/berat bila tanpa filter indikator.
            @set_time_limit(300);

            return Excel::download(
                new DataAgregatDkbExport($waktuId, $kecamatan, $indikator),
                "data-agregat-dkb-{$stamp}.xlsx",
            );
        }

        $orientasiPdf = $pengaturan->orientasi_pdf === 'potrait' ? 'portrait' : 'landscape';

        $pdfExport = new DataAgregatExport($waktuId, $kecamatan, $indikator);

        $pdf = Pdf::loadView('ekspor.pdf', [
            'judul'       => $judul,
            'kolom'       => $pdfExport->kolomAktif(),
            'baris'       => $pdfExport->barisTampil(),
            'kopJudul'    => $pengaturan->kop_judul_tampil,
            'kopSubjudul' => $pengaturan->kop_subjudul_tampil,
            'dicetak'     => now(),
            'oleh'        => $request->user()->name ?? 'Publik',
            'total'       => $total,
        ])->setPaper('a4', $orientasiPdf);

        return $pdf->download("data-agregat-{$stamp}.pdf");
    }

    private function susunJudul(?int $waktuId, ?string $kecamatan, ?string $indikator): string
    {
        $bagian = ['Data Agregat Kependudukan'];

        if ($indikator) {
            $bagian[] = 'Indikator '.str($indikator)->replace('_', ' ')->title();
        }
        if ($kecamatan) {
            $bagian[] = 'Kec. '.$kecamatan;
        }
        if ($waktuId) {
            $bagian[] = optional($this->filter->getWaktuList()->firstWhere('id', $waktuId))->label;
        }

        return implode(' — ', array_filter($bagian));
    }

    private function catat(Request $request, string $judul, string $jenis, array $parameter): void
    {
        Laporan::create([
            'user_id'   => $request->user()?->id,
            'judul'     => $judul,
            'jenis'     => $jenis,
            'parameter' => $parameter,
        ]);

        $this->audit->record(AuditLogService::AKSI_EKSPOR, 'laporans', null, [
            'judul' => $judul,
            'jenis' => $jenis,
        ] + $parameter);
    }
}
