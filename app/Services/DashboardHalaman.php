<?php

namespace App\Services;

use App\Http\Controllers\DemografiController;
use App\Http\Controllers\SosialController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Orkestrator halaman publik "Demografi" & "Sosial".
 *
 * Kedua halaman itu memakai filter yang SAMA PERSIS (kecamatan/kelurahan/
 * periode) dan pola render yang sama (partial _konten + redraw Chart.js dari
 * payload `charts`). Sejak bagian bisa dipindah antar keduanya lewat menu
 * "Bagian Dashboard", setiap kunjungan menghitung metrik KEDUA halaman lalu
 * merender bagian yang halaman DB-nya == halaman yang sedang dibuka. Bagian
 * yang disembunyikan / dipindah / diperkecil membuat sisanya mengalir otomatis
 * (grid cair, lihat resources/css + komponen <x-seksi>).
 */
class DashboardHalaman
{
    public function __construct(
        private FilterWilayahService $filter,
        private SeksiDashboardRegistry $seksi,
        private DemografiController $demografi,
        private SosialController $sosial,
    ) {
    }

    public function tanggapi(string $halaman, Request $request): View|JsonResponse
    {
        $latestWaktu   = $this->filter->getLatestWaktu();
        $waktuList     = $this->filter->getWaktuList();
        $kecamatanList = $this->filter->getKecamatanList();
        $wilayahList   = $this->filter->getWilayahList();

        $waktuId   = $this->filter->periodeTerpilih($request);
        $wilayahId = $request->integer('wilayah_id') ?: null;
        $kecamatan = $request->string('kecamatan')->toString() ?: null;

        $demo  = $this->demografi->hitung($waktuId, $wilayahId, $kecamatan, $waktuList);
        $sos   = $this->sosial->hitung($waktuId, $wilayahId, $kecamatan, $waktuList);

        // Kunci yang sama ('totalPenduduk', 'selectedWaktu') bernilai identik di
        // kedua sisi — aman ditimpa.
        $vars   = [...$demo['vars'], ...$sos['vars']];
        $charts = [...$demo['charts'], ...$sos['charts']];

        $this->seksi->setHalamanAktif($halaman);

        $kontenHtml = (string) view('dashboard._grid', array_merge($vars, [
            'halamanAktif' => $halaman,
        ]))->render();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'waktu_id'    => $waktuId,
                'wilayah_id'  => $wilayahId,
                'kecamatan'   => $kecamatan,
                'konten_html' => $kontenHtml,
                'charts'      => $charts,
            ]);
        }

        return view($halaman.'.index', array_merge($vars, [
            'halamanAktif'  => $halaman,
            'kontenHtml'    => $kontenHtml,
            'charts'        => $charts,
            'waktuList'     => $waktuList,
            'kecamatanList' => $kecamatanList,
            'wilayahList'   => $wilayahList,
            'waktuId'       => $waktuId,
            'wilayahId'     => $wilayahId,
            'kecamatan'     => $kecamatan,
            'latestWaktu'   => $latestWaktu,
        ]));
    }
}
