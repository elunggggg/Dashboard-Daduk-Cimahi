<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Semua halaman harus tetap tampil saat data_agregat & dim_waktu masih kosong —
 * kondisi nyata tepat setelah reset, sebelum berkas DKB pertama diimpor.
 */
class SmokeKosongTest extends TestCase
{
    public function test_halaman_publik_tetap_tampil_tanpa_data(): void
    {
        foreach (['/', '/peta', '/demografi', '/sosial', '/mobilitas', '/metadata'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_halaman_terautentikasi_tetap_tampil_tanpa_data(): void
    {
        $admin = User::where('role', 'petugas')->firstOrFail();

        foreach ([
            '/dashboard',
            '/petugas/wilayah',
            '/petugas/pengguna',
            '/petugas/import',
            '/petugas/audit',
            '/petugas/backup',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
