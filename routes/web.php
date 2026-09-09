<?php

use App\Http\Controllers\Petugas\AuditLogController;
use App\Http\Controllers\Petugas\BackupController;
use App\Http\Controllers\Petugas\ImportController;
use App\Http\Controllers\Petugas\IndikatorController;
use App\Http\Controllers\Petugas\KonfigurasiImportController;
use App\Http\Controllers\Petugas\MetadataController as AdminMetadataController;
use App\Http\Controllers\Petugas\PengaturanController;
use App\Http\Controllers\Petugas\PenggunaController;
use App\Http\Controllers\Petugas\WilayahController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\EksporController;
use App\Http\Controllers\DashboardPublikController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemografiController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\MobilitasController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\SosialController;
use Illuminate\Support\Facades\Route;

// ── Public (tanpa login) ──────────────────────────────────────────────────────
Route::get('/', DashboardPublikController::class)->name('dashboard.publik');
Route::get('/peta',      PetaController::class)->name('peta.index');
Route::get('/demografi', DemografiController::class)->name('demografi.index');
Route::get('/sosial',    SosialController::class)->name('sosial.index');
Route::get('/mobilitas', MobilitasController::class)->name('mobilitas.index');
Route::get('/metadata',  MetadataController::class)->name('metadata.index');

// Ekspor kini bisa dipakai Petugas maupun Publik (tanpa login) — embed di Dashboard Publik.
Route::post('/ekspor', [EksporController::class, 'unduh'])->name('ekspor.unduh');
Route::get('/ekspor/profil-kependudukan', [\App\Http\Controllers\ProfilKependudukanController::class, 'unduh'])->name('ekspor.profil');

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ── Petugas (satu-satunya peran login) ───────────────────────────────────
    Route::middleware(['role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
        // ── Import ────────────────────────────────────────────────────────────
        Route::get('/import',                  [ImportController::class, 'index'])->name('import.index');
        Route::get('/import/template',         [ImportController::class, 'template'])->name('import.template');
        Route::get('/import/template-contoh',  [ImportController::class, 'templateContoh'])->name('import.template-contoh');

        // Mode B — template flat, sekali jalan
        Route::post('/import',                 [ImportController::class, 'store'])->name('import.store');

        // Mode A — berkas DKB mentah: unggah → pratinjau → konfirmasi.
        // Rute statis didaftarkan sebelum yang berparameter agar '/import/dkb'
        // tidak tertangkap sebagai {import}.
        Route::post('/import/dkb',                    [ImportController::class, 'storeDkb'])->name('import.dkb');
        Route::get('/import/{import}/pratinjau',      [ImportController::class, 'pratinjau'])->name('import.pratinjau');
        Route::post('/import/{import}/konfirmasi',    [ImportController::class, 'konfirmasi'])->name('import.konfirmasi');
        Route::post('/import/{import}/batal',         [ImportController::class, 'batal'])->name('import.batal');
        Route::delete('/import/{import}/data',        [ImportController::class, 'hapusData'])->name('import.hapus-data');

        Route::get('/backup',           [BackupController::class, 'index'])->name('backup.index');
        Route::post('/backup',          [BackupController::class, 'store'])->name('backup.store');
        Route::post('/backup/unduh',    [BackupController::class, 'unduh'])->name('backup.unduh');
        Route::delete('/backup',        [BackupController::class, 'destroy'])->name('backup.destroy');

        // Alias didaftarkan sebelum resource agar '/wilayah/alias/{alias}'
        // tidak tertangkap sebagai '/wilayah/{wilayah}'.
        Route::delete('/wilayah/alias/{alias}',  [WilayahController::class, 'hapusAlias'])->name('wilayah.alias.hapus');
        Route::post('/wilayah/{wilayah}/alias',  [WilayahController::class, 'tambahAlias'])->name('wilayah.alias.tambah');
        Route::resource('wilayah', WilayahController::class)->except(['show']);
        Route::resource('pengguna', PenggunaController::class)->except(['show']);
        Route::resource('metadata', AdminMetadataController::class)->except(['show']);
        Route::get('/indikator/{indikator}/data',  [IndikatorController::class, 'data'])->name('indikator.data');
        Route::post('/indikator/{indikator}/data', [IndikatorController::class, 'dataStore'])->name('indikator.data.store');
        Route::patch('/indikator/{indikator}/aktif', [IndikatorController::class, 'toggleAktif'])->name('indikator.toggle-aktif');
        Route::resource('indikator', IndikatorController::class)->except(['show']);

        // Bagian (section) grafik/tabel di halaman publik: tampil/sembunyi,
        // pindah halaman, geser urutan, ubah lebar — semua aksi kecil & instan.
        Route::get('/bagian-dashboard',        [\App\Http\Controllers\Petugas\SeksiDashboardController::class, 'index'])->name('seksi.index');
        Route::post('/bagian-dashboard/reset', [\App\Http\Controllers\Petugas\SeksiDashboardController::class, 'reset'])->name('seksi.reset');
        Route::patch('/bagian-dashboard/{seksi}', [\App\Http\Controllers\Petugas\SeksiDashboardController::class, 'atur'])->name('seksi.atur');

        // Statis didaftarkan sebelum resource supaya '/konfigurasi-import/uji'
        // tidak tertangkap sebagai '/konfigurasi-import/{konfigurasi_import}'.
        Route::get('/konfigurasi-import/uji',  [KonfigurasiImportController::class, 'uji'])->name('konfigurasi-import.uji');
        Route::post('/konfigurasi-import/uji', [KonfigurasiImportController::class, 'ujiProses'])->name('konfigurasi-import.uji-proses');
        Route::resource('konfigurasi-import', KonfigurasiImportController::class)->except(['show']);
        Route::get('/audit', AuditLogController::class)->name('audit.index');

        // ── Pengaturan Tampilan Dashboard Publik ──────────────────────────────────────
        Route::get('/pengaturan',  [PengaturanController::class, 'edit'])->name('pengaturan.edit');
        Route::put('/pengaturan',  [PengaturanController::class, 'update'])->name('pengaturan.update');
    });
});
