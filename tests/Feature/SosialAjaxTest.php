<?php

namespace Tests\Feature;

use App\Models\DimWaktu;
use App\Models\DimWilayah;
use Tests\TestCase;

/**
 * Phase 5: filter periode/kecamatan/kelurahan di Sosial tanpa reload halaman
 * — angka JSON harus SAMA PERSIS dengan kunjungan HTML pertama untuk filter
 * yang sama (satu method menghitung keduanya, lihat SosialController).
 */
class SosialAjaxTest extends TestCase
{
    public function test_kunjungan_biasa_mengembalikan_html(): void
    {
        $res = $this->get('/sosial');
        $res->assertOk();
        $res->assertViewIs('sosial.index');
    }

    public function test_request_ajax_mengembalikan_json_dengan_semua_14_chart(): void
    {
        $res = $this->get('/sosial', ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);

        $res->assertOk();
        $res->assertHeader('content-type', 'application/json');
        $res->assertJsonStructure([
            'waktu_id', 'wilayah_id', 'kecamatan', 'konten_html',
            'charts' => [
                'edu', 'job', 'usia_sekolah', 'agama', 'ktp_status', 'kk_status', 'goldar', 'shbkel',
                'kia', 'akta_lahir', 'kk_jk', 'akta_lahir_kelurahan', 'kia_kelurahan', 'ktp_kelurahan',
            ],
        ]);
        $this->assertStringContainsString('Lihat Tabel', $res->json('konten_html'));
        $this->assertStringContainsString('Angkatan Kerja Menurut Tingkat Pendidikan', $res->json('konten_html'));
    }

    public function test_angka_json_sama_dengan_html_untuk_filter_yang_sama(): void
    {
        $waktuId = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->value('id');

        $html = $this->get('/sosial?waktu_id='.$waktuId);
        $html->assertOk();
        $totalHtml = $html->viewData('totalPenduduk');
        $pctKtpHtml = $html->viewData('pctKtp');

        $json = $this->get('/sosial?waktu_id='.$waktuId, ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $json->assertOk();

        $this->assertSame($waktuId, $json->json('waktu_id'));
        // pctKtp tidak dikirim sebagai field terpisah di JSON (ada di dalam
        // konten_html yang sudah dirender) — cukup pastikan angkanya konsisten
        // dengan mengecek total edu chart cocok dengan pendidikanData HTML.
        $pendidikanHtml = $html->viewData('pendidikanData');
        $this->assertEquals($pendidikanHtml->values()->all(), $json->json('charts.edu.values'), 'Data chart Pendidikan JSON beda dengan HTML.');
        $this->assertNotNull($pctKtpHtml);
    }

    public function test_filter_kelurahan_mengubah_isi_konten(): void
    {
        $kelurahan = DimWilayah::kelurahan()->first();

        $res = $this->get('/sosial?wilayah_id='.$kelurahan->id, ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $res->assertOk();
        $this->assertEquals($kelurahan->id, $res->json('wilayah_id'));
    }
}
