<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\KategoriPublikController;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\Laporan;
use App\Models\PengaturanTampilan;
use App\Services\FilterWilayahService;
use Illuminate\View\View;

class DashboardPublikController extends Controller
{
    public function __construct(private FilterWilayahService $filter) {}

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
    public function __invoke(): View
    {
        $pengaturan = PengaturanTampilan::current();

        // Default box "Perbandingan": data yang PALING BARU DIUPLOAD vs
        // sebelumnya (bukan sekadar tahun/semester terbesar) — lihat
        // DimWaktu::duaTerbaruDiupload().
        $duaTerbaru      = DimWaktu::duaTerbaruDiupload();
        $defaultWaktuId1 = $duaTerbaru->get(1)?->id ?? $duaTerbaru->get(0)?->id;
        $defaultWaktuId2 = $duaTerbaru->get(0)?->id;

        return view('dashboard-publik.index', [
            'latarBelakang'     => $pengaturan->latar_belakang_url,
            'latarBelakangBody' => $pengaturan->latar_belakang_body_url,
            'logoUrl'           => $pengaturan->logo_url,
            'logoInstansiUrl'   => $pengaturan->logo_instansi_url,
            'namaSistem'        => $pengaturan->nama_sistem_tampil,
            'namaInstansi'      => $pengaturan->nama_instansi_tampil,
            'waktuList'         => $this->filter->getWaktuList(),
            'indikatorList'     => KategoriPublikController::INDIKATOR_LIST,
            'kecamatanList'     => $this->filter->getKecamatanList(),
            'eksporIndikatorList' => DimKategori::select('jenis_indikator')->distinct()->orderBy('jenis_indikator')->pluck('jenis_indikator'),
            'riwayatEkspor'     => Laporan::with('user:id,name')->latest('id')->take(10)->get(),
            'defaultWaktuId1'   => $defaultWaktuId1,
            'defaultWaktuId2'   => $defaultWaktuId2,
        ]);
    }
}
