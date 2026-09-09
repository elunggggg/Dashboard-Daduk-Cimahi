<?php

namespace Tests\Feature;

use App\Models\SeksiDashboard;
use App\Models\User;
use App\Services\SeksiDashboardRegistry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * "Reset ke Awal" pada menu Bagian Dashboard harus mengembalikan SELURUH bagian
 * ke tata letak awal (persis sebelum fitur ada) — sumbernya
 * SeksiDashboardRegistry::BAWAAN.
 */
class SeksiDashboardResetTest extends TestCase
{
    // Test ini mengacak-acak & mereset tabel seksi_dashboard — bungkus transaksi
    // supaya konfigurasi "Bagian Dashboard" milik Petugas TIDAK ikut ter-reset
    // setiap kali suite dijalankan (test ini kena DB asli).
    use DatabaseTransactions;

    public function test_bawaan_konsisten_dengan_yang_terdaftar_saat_halaman_dibuka(): void
    {
        // Buka semua halaman publik supaya <x-seksi> mendaftarkan semua bagian
        // (termasuk blok bawaan Dashboard Publik di "/").
        $this->get('/')->assertOk();
        $this->get('/demografi')->assertOk();
        $this->get('/sosial')->assertOk();
        $this->get('/mobilitas')->assertOk();

        $terdaftar = SeksiDashboard::pluck('kunci')->sort()->values()->all();
        $bawaan    = collect(array_keys(SeksiDashboardRegistry::BAWAAN))->sort()->values()->all();

        $this->assertSame(
            $bawaan,
            $terdaftar,
            'Daftar <x-seksi kunci="..."> di Blade tidak sama dengan SeksiDashboardRegistry::BAWAAN — samakan keduanya.'
        );
    }

    public function test_reset_mengembalikan_semua_ke_bawaan(): void
    {
        // Acak-acak dulu.
        SeksiDashboard::query()->update(['tampil' => false]);
        SeksiDashboard::where('halaman', 'demografi')->limit(3)->update(['halaman' => 'sosial', 'lebar' => 'penuh', 'urutan' => 777]);

        $petugas = User::where('role', 'petugas')->firstOrFail();

        $this->actingAs($petugas)
            ->post('/petugas/bagian-dashboard/reset')
            ->assertRedirect(route('petugas.seksi.index'));

        $this->assertSame(count(SeksiDashboardRegistry::BAWAAN), SeksiDashboard::count());
        $this->assertSame(0, SeksiDashboard::where('tampil', false)->count(), 'Setelah reset semua bagian harus tampil.');

        foreach (SeksiDashboardRegistry::BAWAAN as $kunci => $awal) {
            $row = SeksiDashboard::where('kunci', $kunci)->first();
            $this->assertNotNull($row, "Bagian {$kunci} hilang setelah reset.");
            $this->assertSame($awal['halaman'], $row->halaman, "Halaman {$kunci} tidak kembali ke awal.");
            $this->assertSame($awal['urutan'], (int) $row->urutan, "Urutan {$kunci} tidak kembali ke awal.");
            $this->assertSame($awal['lebar'], $row->lebar, "Lebar {$kunci} tidak kembali ke awal.");
        }
    }
}
