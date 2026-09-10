<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Smoke test read-only: buka setiap route GET dan pastikan tidak 5xx.
 * Memakai database pengembangan apa adanya (tidak me-refresh / menulis data).
 */
class SmokeSemuaRouteTest extends TestCase
{
    /** Route publik yang boleh diakses tanpa login. */
    public static function routePublik(): array
    {
        return [
            ['/'], ['/peta'], ['/demografi'], ['/sosial'], ['/mobilitas'],
            ['/metadata'], ['/login'],
            ['/api/dashboard'],
        ];
    }

    /** Route yang wajib login sebagai petugas. */
    public static function routePetugas(): array
    {
        return [
            ['/dashboard'],
            ['/petugas/import'],
            ['/petugas/backup'],
            ['/petugas/wilayah'], ['/petugas/wilayah/create'],
            ['/petugas/pengguna'], ['/petugas/pengguna/create'],
            ['/petugas/metadata'], ['/petugas/metadata/create'],
            ['/petugas/indikator'], ['/petugas/indikator/create'],
            ['/petugas/konfigurasi-export'],
            ['/petugas/audit'],
            ['/petugas/pengaturan'],
        ];
    }

    /** @dataProvider routePublik */
    public function test_route_publik_tidak_error(string $uri): void
    {
        $mulai = microtime(true);
        $res   = $this->get($uri);
        $ms    = round((microtime(true) - $mulai) * 1000);

        fwrite(STDERR, sprintf("\n  %-38s %s  %sms", $uri, $res->getStatusCode(), $ms));
        $this->assertLessThan(500, $res->getStatusCode(), "Route {$uri} mengembalikan 5xx");
    }

    /** @dataProvider routePetugas */
    public function test_route_petugas_tidak_error(string $uri): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $this->assertNotNull($petugas, 'Tidak ada user dengan role petugas di database');

        $mulai = microtime(true);
        $res   = $this->actingAs($petugas)->get($uri);
        $ms    = round((microtime(true) - $mulai) * 1000);

        fwrite(STDERR, sprintf("\n  %-38s %s  %sms", $uri, $res->getStatusCode(), $ms));
        $this->assertLessThan(500, $res->getStatusCode(), "Route {$uri} mengembalikan 5xx");
    }

    /** Route petugas harus menolak tamu (redirect ke login). */
    public function test_route_petugas_menolak_tamu(): void
    {
        foreach (self::routePetugas() as [$uri]) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    /**
     * Endpoint "Perbandingan" HARUS selalu membalas JSON, baik parameter
     * valid maupun tidak — bukan redirect 302 HTML — karena dipanggil lewat
     * fetch() polos di Alpine (lihat KategoriPublikController).
     */
    public function test_api_kategori_publik_selalu_json(): void
    {
        $ok = $this->get('/api/dashboard-publik/kategori?jenis_indikator=jenis_kelamin');
        $ok->assertOk();
        $ok->assertHeader('content-type', 'application/json');

        $gagal = $this->get('/api/dashboard-publik/kategori');
        $gagal->assertStatus(422);
        $gagal->assertHeader('content-type', 'application/json');
        $gagal->assertJsonStructure(['message', 'errors']);
    }
}
