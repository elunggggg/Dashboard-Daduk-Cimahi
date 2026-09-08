<?php

namespace Tests\Feature;

use App\Models\DimWaktu;
use App\Models\DimWilayah;
use Tests\TestCase;

/**
 * Phase 5: filter periode/kecamatan/kelurahan di Demografi tanpa reload
 * halaman — angka JSON harus SAMA PERSIS dengan kunjungan HTML pertama untuk
 * filter yang sama (satu method menghitung keduanya, lihat DemografiController).
 */
class DemografiAjaxTest extends TestCase
{
    public function test_kunjungan_biasa_mengembalikan_html(): void
    {
        $res = $this->get('/demografi');
        $res->assertOk();
        $res->assertViewIs('demografi.index');
    }

    public function test_request_ajax_mengembalikan_json_dengan_semua_6_chart(): void
    {
        $res = $this->get('/demografi', ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);

        $res->assertOk();
        $res->assertHeader('content-type', 'application/json');
        $res->assertJsonStructure([
            'waktu_id', 'wilayah_id', 'kecamatan', 'konten_html',
            'charts' => ['gender', 'anak', 'lansia', 'marital', 'disab', 'piramida', 'total_penduduk'],
        ]);
        $this->assertStringContainsString('Piramida Penduduk', $res->json('konten_html'));
        $this->assertStringContainsString('Lihat Tabel', $res->json('konten_html'));
    }

    public function test_angka_json_sama_dengan_html_untuk_filter_yang_sama(): void
    {
        $waktuId = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->value('id');

        $html = $this->get('/demografi?waktu_id='.$waktuId);
        $html->assertOk();
        $genderHtml = $html->viewData('genderData');
        $totalHtml = $html->viewData('totalPenduduk');

        $json = $this->get('/demografi?waktu_id='.$waktuId, ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $json->assertOk();

        $this->assertSame($waktuId, $json->json('waktu_id'));
        $this->assertEquals($genderHtml->values()->all(), $json->json('charts.gender.values'), 'Data chart Jenis Kelamin JSON beda dengan HTML.');
        $this->assertSame($totalHtml, $json->json('charts.total_penduduk'));
    }

    public function test_filter_kelurahan_mengubah_isi_konten(): void
    {
        $kelurahan = DimWilayah::kelurahan()->first();

        $res = $this->get('/demografi?wilayah_id='.$kelurahan->id, ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $res->assertOk();
        $this->assertEquals($kelurahan->id, $res->json('wilayah_id'));
    }
}
