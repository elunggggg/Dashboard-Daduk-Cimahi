<?php

namespace App\Http\Controllers;

use App\Exports\DataAgregatExport;
use App\Models\Laporan;
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
    // Batas baris untuk PDF — dompdf menyusun seluruh dokumen di memori,
    // ribuan baris membuatnya sangat lambat atau kehabisan memori.
    private const BATAS_BARIS_PDF = 3000;

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

        $query = DataAgregatExport::query($waktuId, $kecamatan, $indikator);
        $total = (clone $query)->count();

        if ($total === 0) {
            return back()->with('error', 'Tidak ada data yang cocok dengan filter — tidak ada yang diekspor.');
        }

        // Diperiksa sebelum apa pun dicatat — ekspor yang ditolak tidak boleh
        // muncul di riwayat laporan seolah-olah berhasil.
        if ($data['format'] === 'pdf' && $total > self::BATAS_BARIS_PDF) {
            return back()->with('error',
                "Hasil filter {$total} baris, melebihi batas ".self::BATAS_BARIS_PDF.' baris untuk PDF. '
                .'Persempit filter atau gunakan format Excel.');
        }

        $judul = $this->susunJudul($waktuId, $kecamatan, $indikator);
        $stamp = now()->format('Ymd-His');

        $this->catat($request, $judul, $data['format'], [
            'waktu_id' => $waktuId, 'kecamatan' => $kecamatan,
            'jenis_indikator' => $indikator, 'jumlah_baris' => $total,
        ]);

        if ($data['format'] === 'excel') {
            return Excel::download(
                new DataAgregatExport($waktuId, $kecamatan, $indikator),
                "data-agregat-{$stamp}.xlsx",
            );
        }

        $pdf = Pdf::loadView('ekspor.pdf', [
            'judul'     => $judul,
            'baris'     => $query->get(),
            'dicetak'   => now(),
            'oleh'      => $request->user()->name ?? 'Publik',
            'total'     => $total,
        ])->setPaper('a4', 'landscape');

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
