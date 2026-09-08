<?php

namespace Tests\Feature;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\User;
use App\Services\DashboardCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Verifikasi cache dashboard (Phase 4/8): endpoint API dashboard di-cache,
 * TAPI harus selalu ikut berubah begitu data_agregat berubah lewat jalur mana
 * pun (koreksi manual, hapus data import). Cache basi (stale) yang tidak
 * pernah di-flush adalah bug paling berbahaya untuk fitur ini — salah-salah
 * Petugas & publik melihat angka lama padahal data sudah dikoreksi.
 */
class CacheDashboardTest extends TestCase
{
    public function test_perubahan_manual_via_indikator_langsung_terlihat_di_api_dashboard(): void
    {
        $petugas = User::where('role', 'petugas')->firstOrFail();
        $waktuId = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->value('id');

        // Ambil satu baris jenis_kelamin nyata untuk diubah nilainya.
        $kategori = DimKategori::where('jenis_indikator', 'jenis_kelamin')->where('label', 'Laki-laki')->firstOrFail();
        $baris = DataAgregat::where('kategori_id', $kategori->id)->where('waktu_id', $waktuId)->firstOrFail();
        $nilaiAsli = $baris->jumlah;

        // 1) Panggil API dashboard — hasil pertama ini akan TERCACHE.
        $respon1 = $this->getJson('/api/dashboard?waktu_id='.$waktuId);
        $respon1->assertOk();
        $totalAwal = $respon1->json('kpi.penduduk.total');
        $this->assertIsInt($totalAwal);

        // 2) Ubah salah satu baris lewat halaman Kelola Indikator (jalur yang
        //    HARUS memanggil DashboardCacheService::flush()).
        $nilaiBaru = $nilaiAsli + 777;
        $simpan = $this->actingAs($petugas)->post(route('petugas.indikator.data.store', $kategori), [
            'wilayah_id' => $baris->wilayah_id,
            'waktu_id'   => $waktuId,
            'jumlah'     => $nilaiBaru,
        ]);
        $simpan->assertRedirect();

        // 3) Panggil API dashboard LAGI dengan filter yang SAMA PERSIS — kalau
        //    cache tidak di-flush, ini akan mengembalikan $totalAwal yang basi.
        $respon2 = $this->getJson('/api/dashboard?waktu_id='.$waktuId);
        $respon2->assertOk();
        $totalSesudah = $respon2->json('kpi.penduduk.total');

        $this->assertSame(
            $totalAwal + 777,
            $totalSesudah,
            'Cache dashboard TIDAK ter-flush setelah koreksi manual — angka yang tampil basi.'
        );

        // Bersihkan: kembalikan nilai semula supaya test lain / data riil tidak berubah permanen.
        $baris->update(['jumlah' => $nilaiAsli]);
        app(DashboardCacheService::class)->flush();
    }

    public function test_versi_cache_naik_setiap_flush_dipanggil(): void
    {
        $cache = app(DashboardCacheService::class);
        $versiAwal = $cache->versi();

        $cache->flush();

        $this->assertSame($versiAwal + 1, $cache->versi());
    }

    public function test_key_cache_berbeda_untuk_filter_berbeda(): void
    {
        $cache = app(DashboardCacheService::class);

        $keyA = $cache->key('api-dashboard', ['Cimahi Selatan', null, 56]);
        $keyB = $cache->key('api-dashboard', ['Cimahi Utara', null, 56]);

        $this->assertNotSame($keyA, $keyB);
    }

    protected function tearDown(): void
    {
        // Jangan biarkan versi cache yang dinaikkan test ini bocor ke test lain.
        Cache::forget('dashboard_cache_versi');
        parent::tearDown();
    }
}
