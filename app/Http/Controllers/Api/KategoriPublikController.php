<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataAgregat;
use App\Models\DimWaktu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Memasok data untuk box "Perbandingan" di Dashboard Publik: satu kategori
 * (jenis_indikator) + DUA periode → rincian per label kategori itu untuk
 * kedua periode berdampingan (mis. Wajib KTP Semester 1 vs Semester 2),
 * seluruh kota (tidak ada filter wilayah di box ini).
 */
class KategoriPublikController extends Controller
{
    // Indikator yang tersedia untuk box "Perbandingan" di Dashboard Publik/Petugas.
    public const INDIKATOR_LIST = [
        'jenis_kelamin'    => 'Jenis Kelamin',
        'kelompok_umur'    => 'Kelompok Umur',
        'status_kawin'     => 'Status Perkawinan',
        'disabilitas'      => 'Disabilitas',
        'pendidikan'       => 'Pendidikan',
        'pekerjaan'        => 'Pekerjaan',
        'agama'            => 'Agama',
        'golongan_darah'   => 'Golongan Darah',
        'kepemilikan_ktp'  => 'Kepemilikan KTP-el',
        'kepemilikan_kk'   => 'Kepemilikan KK',
        'kepemilikan_kia'  => 'Kepemilikan KIA',
        'akta_lahir'       => 'Akta Kelahiran',
        'akta_kawin'       => 'Akta Perkawinan',
        'akta_cerai'       => 'Akta Perceraian',
        'mobilitas_datang' => 'Mobilitas Datang',
        'mobilitas_pindah' => 'Mobilitas Pindah',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis_indikator' => ['required', 'string', Rule::in(array_keys(self::INDIKATOR_LIST))],
            'waktu_id_1'      => ['nullable', 'integer', 'exists:dim_waktu,id'],
            'waktu_id_2'      => ['nullable', 'integer', 'exists:dim_waktu,id'],
        ]);

        $allWaktu  = DimWaktu::query()->orderBy('tahun')->orderBy('semester')->get();

        // Default (kalau waktu_id_1/2 tidak dikirim): dua periode yang PALING
        // BARU DIUPLOAD, bukan yang tahun/semesternya paling besar — lihat
        // DimWaktu::duaTerbaruDiupload().
        $duaTerbaru = DimWaktu::duaTerbaruDiupload();
        $waktuId1  = $validated['waktu_id_1'] ?? ($duaTerbaru->get(1)?->id ?? $duaTerbaru->get(0)?->id);
        $waktuId2  = $validated['waktu_id_2'] ?? $duaTerbaru->get(0)?->id;

        if (! $waktuId1 || ! $waktuId2) {
            return response()->json(['labels' => [], 'nilai1' => [], 'nilai2' => [], 'periode1' => null, 'periode2' => null]);
        }

        $data1 = $this->aggr($validated['jenis_indikator'], $waktuId1);
        $data2 = $this->aggr($validated['jenis_indikator'], $waktuId2);

        $labels = $data1->keys()->merge($data2->keys())->unique()->values();

        return response()->json([
            'labels'   => $labels,
            'nilai1'   => $labels->map(fn ($l) => $data1->get($l, 0))->values(),
            'nilai2'   => $labels->map(fn ($l) => $data2->get($l, 0))->values(),
            'periode1' => $allWaktu->firstWhere('id', $waktuId1)?->label,
            'periode2' => $allWaktu->firstWhere('id', $waktuId2)?->label,
        ]);
    }

    private function aggr(string $jenis, int $waktuId): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
            ->where('waktu_id', $waktuId)
            ->with('kategori')
            ->get()
            ->sortBy('kategori.urutan')
            ->groupBy('kategori.label')
            ->map(fn ($rows) => (int) $rows->sum('jumlah'));
    }
}
