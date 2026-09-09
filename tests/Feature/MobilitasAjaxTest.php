<?php

namespace Tests\Feature;

use App\Models\DimWaktu;
use Tests\TestCase;

/**
 * Mobilitas kini ikut mesin grid terpadu (DashboardHalaman) bersama
 * Dashboard/Demografi/Sosial — filter periode/wilayah tanpa reload, respons
 * JSON berstruktur sama (konten_html + charts). Angka JSON harus sama dengan
 * kunjungan HTML pertama untuk filter yang sama.
 */
class MobilitasAjaxTest extends TestCase
{
    public function test_kunjungan_biasa_mengembalikan_html(): void
    {
        $res = $this->get('/mobilitas');
        $res->assertOk();
        $res->assertViewIs('mobilitas.index');
    }

    public function test_request_ajax_mengembalikan_json_bukan_html(): void
    {
        $res = $this->get('/mobilitas', ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);

        $res->assertOk();
        $res->assertHeader('content-type', 'application/json');
        $res->assertJsonStructure([
            'waktu_id', 'wilayah_id', 'kecamatan', 'konten_html',
            'charts' => [
                'datang' => ['labels', 'values', 'total'],
                'pindah' => ['labels', 'values', 'total'],
                'trend'  => ['labels', 'datang', 'pindah'],
            ],
        ]);

        $this->assertStringContainsString('Pendatang per Kelurahan', $res->json('konten_html'));
        $this->assertStringContainsString('Lihat Tabel', $res->json('konten_html'));
    }

    public function test_angka_json_sama_dengan_html_untuk_filter_yang_sama(): void
    {
        $waktuId = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->value('id');

        $html = $this->get('/mobilitas?waktu_id='.$waktuId);
        $html->assertOk();
        $totalDatangHtml = $html->viewData('totalDatang');
        $totalPindahHtml = $html->viewData('totalPindah');

        $json = $this->get('/mobilitas?waktu_id='.$waktuId, ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $json->assertOk();

        $this->assertSame($waktuId, $json->json('waktu_id'));
        $this->assertSame($totalDatangHtml, $json->json('charts.datang.total'), 'Total Datang JSON beda dengan HTML.');
        $this->assertSame($totalPindahHtml, $json->json('charts.pindah.total'), 'Total Pindah JSON beda dengan HTML.');
    }
}
