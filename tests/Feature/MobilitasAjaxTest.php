<?php

namespace Tests\Feature;

use App\Models\DimWaktu;
use Tests\TestCase;

/**
 * Phase 5: filter periode di Mobilitas HARUS bisa dipakai tanpa reload
 * halaman (fetch AJAX ke route yang sama, respons JSON) — dan yang paling
 * penting, angka JSON itu HARUS SAMA PERSIS dengan yang dirender di kunjungan
 * HTML pertama (satu method menghitung keduanya, lihat MobilitasController).
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
            'waktu_id', 'kpi' => ['total_datang', 'total_pindah', 'saldo', 'rasio_pindah_datang', 'periode_label'],
            'datang' => ['labels', 'values', 'total'],
            'pindah' => ['labels', 'values', 'total'],
            'rincian_html' => ['datang', 'pindah'],
        ]);

        // HTML komponen <x-rincian-indikator> harus benar-benar dirender, bukan string kosong.
        $this->assertStringContainsString('Lihat Tabel', $res->json('rincian_html.datang'));
        $this->assertStringContainsString('Lihat Tabel', $res->json('rincian_html.pindah'));
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

        $this->assertSame($totalDatangHtml, $json->json('kpi.total_datang'), 'Total Datang JSON beda dengan HTML untuk filter yang sama.');
        $this->assertSame($totalPindahHtml, $json->json('kpi.total_pindah'), 'Total Pindah JSON beda dengan HTML untuk filter yang sama.');
    }

    public function test_filter_semua_periode_mengembalikan_gabungan(): void
    {
        // waktu_id= (kosong eksplisit) → "Semua Periode", bolehSemua:true khusus halaman ini.
        $res = $this->get('/mobilitas?waktu_id=', ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest']);
        $res->assertOk();
        $this->assertNull($res->json('waktu_id'));
        $this->assertSame('Semua Periode', $res->json('kpi.periode_label'));
    }
}
