<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\KategoriPublikController;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\Metadata;
use App\Models\PengaturanExport;
use App\Models\PengaturanTampilan;
use App\Services\DashboardHalaman;
use App\Services\FilterWilayahService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardPublikController extends Controller
{
    public function __construct(
        private FilterWilayahService $filter,
        private DashboardHalaman $halaman,
    ) {}

    /**
     * Dashboard Publik 2026-08-20 (v2): hanya menampilkan KPI kota (Wajib KTP, Jumlah
     * Penduduk, Jumlah Kepala Keluarga) — bukan lagi satu halaman terpadu
     * dengan filter wilayah/peta/seluruh grafik (versi 2026-08-15). Di bawah
     * KPI ada box "Perbandingan": pilih kategori + periode, tampilkan satu
     * bar chart rincian label kategori itu (lihat Api\KategoriPublikController).
     * Drill-down per wilayah/periode/kategori lengkap tetap ada di modul
     * masing-masing (/demografi, /sosial, dst).
     *
     * Ekspor Laporan (2026-08-25) juga digabung ke sini — dulu halaman /ekspor
     * terpisah yang hanya bisa diakses Petugas, sekarang siapa pun (Petugas
     * atau Publik tanpa login) bisa mengekspor Excel/PDF dari halaman ini.
     */
    public function __invoke(Request $request): View
    {
        $pengaturan = PengaturanTampilan::current();

        // Default box "Perbandingan": data yang PALING BARU DIUPLOAD vs
        // sebelumnya (bukan sekadar tahun/semester terbesar) — lihat
        // DimWaktu::duaTerbaruDiupload().
        $duaTerbaru      = DimWaktu::duaTerbaruDiupload();
        $defaultWaktuId1 = $duaTerbaru->get(1)?->id ?? $duaTerbaru->get(0)?->id;
        $defaultWaktuId2 = $duaTerbaru->get(0)?->id;

        // Zona "Bagian Dashboard" — bagian modul apa pun yang dipindah Petugas
        // ke halaman "dashboard". Se-Kota, periode terbaru (tanpa filter di sini).
        $grid = $this->halaman->susun('dashboard', $request);

        $pengaturanExport = PengaturanExport::current();

        return view('dashboard-publik.index', [
            'kontenGrid'        => $grid['kontenHtml'],
            'gridCharts'        => $grid['charts'],
            'latarBelakang'     => $pengaturan->latar_belakang_url,
            'latarBelakangBody' => $pengaturan->latar_belakang_body_url,
            'logoUrl'           => $pengaturan->logo_url,
            'logoInstansiUrl'   => $pengaturan->logo_instansi_url,
            'namaSistem'        => $pengaturan->nama_sistem_tampil,
            'namaInstansi'      => $pengaturan->nama_instansi_tampil,
            'waktuList'         => $this->filter->getWaktuList(),
            'indikatorList'     => KategoriPublikController::INDIKATOR_LIST,
            'kecamatanList'     => $this->filter->getKecamatanList(),
            // Pilihan indikator Ekspor: kode → nama terbaca (pakai nama Metadata
            // kalau ada, kalau tidak "manusiakan" kodenya), diurutkan A–Z nama.
            'eksporIndikatorList' => $this->pilihanIndikatorEkspor(),
            // Bawaan format & batas PDF dari Konfigurasi Export.
            'eksporFormatBawaan' => $pengaturanExport->format_bawaan,
            'eksporBatasPdf'     => $pengaturanExport->batas_baris_pdf,
            'defaultWaktuId1'   => $defaultWaktuId1,
            'defaultWaktuId2'   => $defaultWaktuId2,
        ]);
    }

    /** @return array<string,string>  kode jenis_indikator => nama terbaca */
    private function pilihanIndikatorEkspor(): array
    {
        $metaNama = Metadata::pluck('nama', 'jenis_indikator');

        $bentang = [
            'ak_pendidikan' => 'angkatan_kerja_menurut_pendidikan',
            'kk_'           => 'kepala_keluarga_',
            '_ku_'          => '_menurut_kelompok_umur_',
            'usklh'         => 'usia_sekolah',
            'shbkel'        => 'status_hubungan_dalam_keluarga',
            'goldar'        => 'golongan_darah',
            'lpp'           => 'laju_pertumbuhan_penduduk',
        ];

        $semua = DimKategori::query()->distinct()->pluck('jenis_indikator');

        return $semua
            // Sembunyikan varian rincian jenis kelamin ({jenis}_l / {jenis}_p)
            // bila indikator induknya ada — ekspor induknya kini otomatis
            // memuat kolom Laki-laki & Perempuan (lihat DataAgregatExport).
            ->reject(fn (string $j) => (str_ends_with($j, '_l') || str_ends_with($j, '_p'))
                && $semua->contains(substr($j, 0, -2)))
            ->mapWithKeys(function (string $j) use ($metaNama, $bentang) {
                $nama = $metaNama->get($j)
                    ?? Str::headline(str_replace(array_keys($bentang), array_values($bentang), $j));

                return [$j => $nama];
            })
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->all();
    }
}
