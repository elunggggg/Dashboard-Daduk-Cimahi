<?php

namespace Tests\Feature;

use App\Models\DimWilayah;
use Tests\TestCase;

/**
 * Phase 5: filter kecamatan/kelurahan di Peta tanpa reload halaman — dan
 * angka JSON-nya harus sama persis dengan kunjungan HTML biasa untuk filter
 * yang sama (satu method menghitung keduanya, lihat PetaController).
 */
class PetaAjaxTest extends TestCase
{
    public function test_kunjungan_biasa_mengembalikan_html(): void
    {
        $res = $this->get('/peta');
        $res->assertOk();
        $res->assertViewIs('peta.index');
    }

    public function test_request_ajax_mengembalikan_json_dengan_stats_html(): void
    {
        $res = $this->get('/peta', ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);

        $res->assertOk();
        $res->assertHeader('content-type', 'application/json');
        $res->assertJsonStructure(['kecamatan', 'kelurahan_id', 'total_terpilih', 'jumlah_kelurahan', 'kelurahan_nama', 'stats_html']);
        $this->assertNotEmpty($res->json('stats_html'));
    }

    public function test_filter_kelurahan_menurunkan_kecamatan_otomatis(): void
    {
        $kelurahan = DimWilayah::kelurahan()->first();

        $res = $this->get('/peta?kelurahan='.$kelurahan->id, ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $res->assertOk();

        $this->assertSame($kelurahan->nama_kecamatan, $res->json('kecamatan'));
        // kelurahan_id lolos dari $request->validate(), bukan $request->integer() —
        // tetap string dari query param (pre-existing, bukan hasil perubahan Phase 5).
        $this->assertEquals($kelurahan->id, $res->json('kelurahan_id'));
        $this->assertStringContainsString($kelurahan->nama_kelurahan, $res->json('stats_html'));
    }

    public function test_angka_json_sama_dengan_html_untuk_filter_yang_sama(): void
    {
        $kecamatan = DimWilayah::kelurahan()->value('nama_kecamatan');

        $html = $this->get('/peta?kecamatan='.urlencode($kecamatan));
        $html->assertOk();
        $totalHtml = $html->viewData('totalTerpilih');

        $json = $this->get('/peta?kecamatan='.urlencode($kecamatan), ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $json->assertOk();

        $this->assertSame($totalHtml, $json->json('total_terpilih'), 'Total terpilih JSON beda dengan HTML untuk filter yang sama.');
    }
}
