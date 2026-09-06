<?php

namespace App\Http\Controllers;

use App\Models\DimKategori;
use App\Models\DimWilayah;
use App\Services\FilterWilayahService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PetaController extends Controller
{
    public function __construct(private FilterWilayahService $filter) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan' => ['nullable', 'integer', 'exists:dim_wilayah,id'],
        ]);

        $kelurahanId = $validated['kelurahan'] ?? null;
        $kecamatan   = ($validated['kecamatan'] ?? null) ?: null;

        // Kelurahan lebih spesifik daripada kecamatan — kecamatannya diturunkan
        // dari kelurahan terpilih supaya kombinasi filter tidak pernah bentrok.
        $kelurahanTerpilih = $kelurahanId ? DimWilayah::find($kelurahanId) : null;
        if ($kelurahanTerpilih) {
            $kecamatan = $kelurahanTerpilih->nama_kecamatan;
        }

        $latestWaktu    = $this->filter->getLatestWaktu();
        $kecamatanList  = $this->filter->getKecamatanList();
        $pendudukKatIds = DimKategori::where('jenis_indikator', 'jenis_kelamin')->pluck('id');

        // Selalu ambil SEMUA wilayah: dipakai untuk dropdown dependen dan untuk
        // mewarnai kecamatan yang sedang tidak difilter.
        $semuaWilayah = DimWilayah::query()
            ->kelurahan()
            ->when($latestWaktu, fn ($q) => $q->withSum([
                'dataAgregat as total_penduduk' => fn ($q) => $q
                    ->whereIn('kategori_id', $pendudukKatIds)
                    ->where('waktu_id', $latestWaktu->id),
            ], 'jumlah'))
            ->orderBy('nama_kecamatan')
            ->orderBy('nama_kelurahan')
            ->get();

        // Statistik panel — mengikuti filter aktif
        $wilayahStats = $semuaWilayah
            ->when($kecamatan, fn ($c) => $c->where('nama_kecamatan', $kecamatan))
            ->when($kelurahanId, fn ($c) => $c->where('id', $kelurahanId))
            ->values();

        $kecamatanStats = $wilayahStats->groupBy('nama_kecamatan')
            ->map(fn ($rows) => [
                'total_penduduk'   => $rows->sum('total_penduduk'),
                'jumlah_kelurahan' => $rows->count(),
                'kelurahan'        => $rows->values(),
            ]);

        // Choropleth memakai total kecamatan PENUH (tidak difilter) agar gradasi
        // warna antar kecamatan tetap sebanding ketika filter aktif.
        $kecamatanPenuh = $semuaWilayah->groupBy('nama_kecamatan')
            ->map(fn ($rows) => (int) $rows->sum('total_penduduk'));

        // Choropleth PETA sekarang di level kelurahan (poligon RW/kelurahan/
        // kecamatan asli sudah tersedia — lihat batas-*-cimahi.geojson).
        // Dikunci ke wilayah_id, bukan nama, supaya tidak rusak oleh ejaan.
        $pendudukPerWilayah = $semuaWilayah->pluck('total_penduduk', 'id')
            ->map(fn ($v) => (int) $v);

        return view('peta.index', [
            'latestWaktu'       => $latestWaktu,
            'kecamatanList'     => $kecamatanList,
            'wilayahStats'      => $wilayahStats,
            'kecamatanStats'    => $kecamatanStats,
            'kecamatanPenuh'    => $kecamatanPenuh,
            'pendudukPerWilayah' => $pendudukPerWilayah,
            'semuaWilayah'      => $semuaWilayah,
            'kecamatan'         => $kecamatan,
            'kelurahanId'       => $kelurahanId,
            'kelurahanTerpilih' => $kelurahanTerpilih,
            'totalTerpilih'     => (int) $wilayahStats->sum('total_penduduk'),
        ]);
    }
}
