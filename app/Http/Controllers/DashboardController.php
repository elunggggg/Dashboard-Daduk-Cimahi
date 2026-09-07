<?php

namespace App\Http\Controllers;

use App\Models\DataAgregat;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use App\Models\ImportExcel;
use App\Models\User;
use Illuminate\View\View;

/**
 * Dashboard Petugas (2026-08-25): bukan lagi KPI+Perbandingan (itu sekarang
 * hanya di Dashboard Publik, lihat DashboardPublikController) — sekarang
 * jadi halaman menu/hub yang menautkan ke seluruh fitur "Halaman Petugas"
 * (lihat daftar yang sama di sidebar, components/layouts/app.blade.php).
 */
class DashboardController extends Controller
{
    public const MENU = [
        ['route' => 'petugas.import.index', 'label' => 'Import Data', 'icon' => 'bi-upload', 'deskripsi' => 'Unggah data DKB mentah atau template flat ke database.', 'warna' => 'blue'],
        ['route' => 'petugas.wilayah.index', 'label' => 'Kelola Wilayah', 'icon' => 'bi-geo-alt', 'deskripsi' => 'Kelola data kelurahan/kecamatan dan alias nama wilayah.', 'warna' => 'emerald'],
        ['route' => 'petugas.pengguna.index', 'label' => 'Kelola Pengguna', 'icon' => 'bi-person-gear', 'deskripsi' => 'Kelola akun Petugas yang bisa masuk ke sistem.', 'warna' => 'violet'],
        ['route' => 'petugas.metadata.index', 'label' => 'Kelola Metadata', 'icon' => 'bi-book', 'deskripsi' => 'Kelola definisi, satuan, dan sumber tiap indikator.', 'warna' => 'amber'],
        ['route' => 'petugas.indikator.index', 'label' => 'Kelola Indikator', 'icon' => 'bi-list-check', 'deskripsi' => 'Aktifkan/nonaktifkan indikator dan koreksi nilai per kelurahan.', 'warna' => 'indigo'],
        ['route' => 'petugas.konfigurasi-import.index', 'label' => 'Konfigurasi Import', 'icon' => 'bi-diagram-3', 'deskripsi' => 'Atur pemetaan sheet & header berkas DKB untuk proses import.', 'warna' => 'rose'],
        ['route' => 'petugas.pengaturan.edit', 'label' => 'Tampilan Dashboard', 'icon' => 'bi-image', 'deskripsi' => 'Ganti header dan logo aplikasi pada Dashboard Publik.', 'warna' => 'pink'],
        ['route' => 'petugas.audit.index', 'label' => 'Audit Log', 'icon' => 'bi-journal-text', 'deskripsi' => 'Riwayat seluruh aktivitas Petugas di sistem.', 'warna' => 'slate'],
        ['route' => 'petugas.backup.index', 'label' => 'Backup Database', 'icon' => 'bi-database-check', 'deskripsi' => 'Buat dan unduh cadangan database.', 'warna' => 'teal'],
    ];

    public function __invoke(): View
    {
        $importTerakhir = ImportExcel::where('status', 'berhasil')->latest('id')->first();

        return view('dashboard.index', [
            'menu' => self::MENU,
            'stat' => [
                'wilayah'    => DimWilayah::kelurahan()->count(),
                'pengguna'   => User::count(),
                'baris_data' => DataAgregat::count(),
                'periode'    => DimWaktu::count(),
                'import_terakhir' => $importTerakhir?->created_at,
            ],
        ]);
    }
}
