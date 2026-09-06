<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SmokeModalTest extends TestCase
{
    private const EMAIL_UJI = 'smoke-modal@uji.invalid';

    protected function setUp(): void
    {
        parent::setUp();
        User::where('email', self::EMAIL_UJI)->forceDelete();
    }

    protected function tearDown(): void
    {
        User::where('email', self::EMAIL_UJI)->forceDelete();
        parent::tearDown();
    }

    public function test_halaman_admin_dengan_modal_konfirmasi_terender(): void
    {
        $admin = User::where('role', 'petugas')->firstOrFail();

        // Tombol Hapus di Kelola Pengguna sengaja tidak muncul untuk akun sendiri,
        // jadi tanpa akun lain halaman itu tidak punya modal sama sekali dan test
        // ini gagal karena isi database, bukan karena kodenya. Akunnya dibuat di
        // sini dan dibuang lagi di tearDown.
        User::create([
            'name'     => 'Akun Uji Modal',
            'email'    => self::EMAIL_UJI,
            'password' => bcrypt(str()->random(32)),
            'role'     => 'petugas',
        ]);

        foreach ([
            '/petugas/wilayah',
            '/petugas/pengguna',
            '/petugas/import',
            '/petugas/backup',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('x-teleport', false);
        }

        $wilayah = \App\Models\DimWilayah::firstOrFail();
        $this->actingAs($admin)->get("/petugas/wilayah/{$wilayah->id}/edit")->assertOk();
    }
}
