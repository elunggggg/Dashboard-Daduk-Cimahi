<?php

namespace Tests\Feature;

use App\Models\DimKategori;
use App\Models\User;
use Tests\TestCase;

/** Smoke test halaman "Data" per indikator (chart per kelurahan). */
class SmokeIndikatorDataTest extends TestCase
{
    public function test_halaman_data_indikator_terbuka(): void
    {
        $petugas   = User::where('role', 'petugas')->firstOrFail();
        $indikator = DimKategori::where('jenis_indikator', 'jenis_kelamin')->firstOrFail();

        $res = $this->actingAs($petugas)->get("/petugas/indikator/{$indikator->id}/data");

        fwrite(STDERR, "\n  /petugas/indikator/{$indikator->id}/data  ".$res->getStatusCode());
        $res->assertOk();
        $res->assertSee('per Kelurahan', false);
    }
}
