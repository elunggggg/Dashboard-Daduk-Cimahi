<?php

namespace App\Services;

use App\Http\Controllers\DemografiController;
use App\Http\Controllers\MobilitasController;
use App\Http\Controllers\SosialController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Orkestrator halaman publik ber-grid cair: Demografi, Sosial, Mobilitas, dan
 * zona bagian pada Dashboard Publik.
 *
 * Ketiga modul memakai filter yang sama (kecamatan/kelurahan/periode). Sejak
 * bagian bisa dipindah antar keempatnya lewat menu "Bagian Dashboard", setiap
 * kunjungan menghitung metrik SEMUA modul lalu merender bagian yang halaman
 * DB-nya == halaman yang sedang dibuka. Bagian yang disembunyikan / dipindah /
 * diperkecil membuat sisanya mengalir otomatis (grid cair).
 */
class DashboardHalaman
{
    public function __construct(
        private FilterWilayahService $filter,
        private SeksiDashboardRegistry $seksi,
        private DemografiController $demografi,
        private SosialController $sosial,
        private MobilitasController $mobilitas,
        private MetrikKota $kota,
    ) {
    }

    /**
     * @return array{konten_html:string, charts:array, vars:array, waktuId:?int, wilayahId:?int, kecamatan:?string}
     */
    public function susun(string $halaman, Request $request): array
    {
        $waktuList = $this->filter->getWaktuList();

        // Zona di Dashboard Publik SENGAJA tanpa filter → selalu se-Kota, periode
        // terbaru. Modul lain memakai filter dari request.
        if ($halaman === 'dashboard') {
            $waktuId = $this->filter->getLatestWaktu()?->id;
            $wilayahId = null;
            $kecamatan = null;
        } else {
            $waktuId   = $this->filter->periodeTerpilih($request);
            $wilayahId = $request->integer('wilayah_id') ?: null;
            $kecamatan = $request->string('kecamatan')->toString() ?: null;
        }

        $demo = $this->demografi->hitung($waktuId, $wilayahId, $kecamatan, $waktuList);
        $sos  = $this->sosial->hitung($waktuId, $wilayahId, $kecamatan, $waktuList);
        $mob  = $this->mobilitas->hitung($waktuId, $wilayahId, $kecamatan, $waktuList);
        $kota = $this->kota->hitung($waktuList);

        $vars   = [...$demo['vars'], ...$sos['vars'], ...$mob['vars'], ...$kota['vars']];
        // Kunci antar-modul tidak bentrok.
        $charts = [...$demo['charts'], ...$sos['charts'], ...$mob['charts'], ...$kota['charts']];

        // Var untuk bagian "Perbandingan Antar Periode" (dashboard/_konten_perbandingan).
        $duaWaktu = \App\Models\DimWaktu::duaTerbaruDiupload();
        $vars['waktuListPerbandingan'] = $waktuList;
        $vars['perbandinganIndikator'] = \App\Http\Controllers\Api\KategoriPublikController::INDIKATOR_LIST;
        $vars['pw1'] = $duaWaktu->get(1)?->id ?? $duaWaktu->get(0)?->id;
        $vars['pw2'] = $duaWaktu->get(0)?->id;

        $this->seksi->setHalamanAktif($halaman);

        $kontenHtml = (string) view('dashboard._grid', array_merge($vars, [
            'halamanAktif' => $halaman,
        ]))->render();

        return compact('kontenHtml', 'charts', 'vars', 'waktuId', 'wilayahId', 'kecamatan');
    }

    public function tanggapi(string $halaman, Request $request): View|JsonResponse
    {
        $latestWaktu   = $this->filter->getLatestWaktu();
        $waktuList     = $this->filter->getWaktuList();
        $kecamatanList = $this->filter->getKecamatanList();
        $wilayahList   = $this->filter->getWilayahList();

        $d = $this->susun($halaman, $request);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'waktu_id'    => $d['waktuId'],
                'wilayah_id'  => $d['wilayahId'],
                'kecamatan'   => $d['kecamatan'],
                'konten_html' => $d['kontenHtml'],
                'charts'      => $d['charts'],
            ]);
        }

        return view($halaman.'.index', array_merge($d['vars'], [
            'halamanAktif'  => $halaman,
            'kontenHtml'    => $d['kontenHtml'],
            'charts'        => $d['charts'],
            'waktuList'     => $waktuList,
            'kecamatanList' => $kecamatanList,
            'wilayahList'   => $wilayahList,
            'waktuId'       => $d['waktuId'],
            'wilayahId'     => $d['wilayahId'],
            'kecamatan'     => $d['kecamatan'],
            'latestWaktu'   => $latestWaktu,
        ]));
    }
}
