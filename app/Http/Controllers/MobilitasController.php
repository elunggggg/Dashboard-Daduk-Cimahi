<?php

namespace App\Http\Controllers;

use App\Models\DataAgregat;
use App\Models\DimWaktu;
use App\Services\FilterWilayahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MobilitasController extends Controller
{
    public function __construct(private FilterWilayahService $filter) {}

    public function __invoke(Request $request): View|JsonResponse
    {
        return app(\App\Services\DashboardHalaman::class)->tanggapi('mobilitas', $request);
    }

    /**
     * Metrik Mobilitas untuk satu kombinasi filter. Bagian per-kelurahan &
     * tren SENGAJA tidak ikut filter wilayah (selalu 15 kelurahan / seluruh
     * riwayat) — hanya periode yang berlaku. Mengembalikan ['vars'=>..., 'charts'=>...].
     */
    public function hitung(?int $waktuId, ?int $wilayahId, ?string $kecamatan, $waktuList): array
    {
        $datangData = $this->perKelurahan('mobilitas_datang', $waktuId);
        $pindahData = $this->perKelurahan('mobilitas_pindah', $waktuId);

        // Rincian jenis kelamin per kelurahan (kolom L/P tabel).
        $datangLakiData      = $this->perKelurahan('mobilitas_datang_l', $waktuId);
        $datangPerempuanData = $this->perKelurahan('mobilitas_datang_p', $waktuId);
        $pindahLakiData      = $this->perKelurahan('mobilitas_pindah_l', $waktuId);
        $pindahPerempuanData = $this->perKelurahan('mobilitas_pindah_p', $waktuId);

        $totalDatang = (int) $datangData->sum();
        $totalPindah = (int) $pindahData->sum();
        $saldo       = $totalDatang - $totalPindah;
        $rasioPindahDatang = $totalDatang > 0 ? round($totalPindah / $totalDatang * 100, 1) : 0;

        $selectedWaktu = $waktuList->firstWhere('id', $waktuId);

        $trendDatang = $this->trendPerPeriode('mobilitas_datang', $waktuList);
        $trendPindah = $this->trendPerPeriode('mobilitas_pindah', $waktuList);

        $vars = [
            'datangData' => $datangData, 'pindahData' => $pindahData,
            'datangLakiData' => $datangLakiData, 'datangPerempuanData' => $datangPerempuanData,
            'pindahLakiData' => $pindahLakiData, 'pindahPerempuanData' => $pindahPerempuanData,
            'totalDatang' => $totalDatang, 'totalPindah' => $totalPindah,
            'saldo' => $saldo, 'rasioPindahDatang' => $rasioPindahDatang,
            'trendDatang' => $trendDatang, 'trendPindah' => $trendPindah,
            'periodeLabelMobilitas' => $selectedWaktu->label ?? ($waktuId === null ? 'Semua Periode' : '-'),
        ];

        $charts = [
            'datang' => ['labels' => $datangData->keys()->values(), 'values' => $datangData->values(), 'total' => $totalDatang],
            'pindah' => ['labels' => $pindahData->keys()->values(), 'values' => $pindahData->values(), 'total' => $totalPindah],
            'trend'  => [
                'labels' => $trendDatang->pluck('label')->values(),
                'datang' => $trendDatang->pluck('total')->values(),
                'pindah' => $trendPindah->pluck('total')->values(),
            ],
        ];

        return ['vars' => $vars, 'charts' => $charts];
    }

    /** Total per kelurahan (nama_kelurahan => jumlah), diurutkan terbanyak. */
    private function perKelurahan(string $jenis, ?int $waktuId): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->with('wilayah')
            ->get()
            ->groupBy(fn ($row) => $row->wilayah->nama_kelurahan)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortDesc();
    }

    private function trendPerPeriode(string $jenis, Collection $waktuList): Collection
    {
        return $waktuList->map(fn (DimWaktu $w) => [
            'label' => $w->label,
            'total' => (int) DataAgregat::whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
                ->where('waktu_id', $w->id)
                ->sum('jumlah'),
        ]);
    }
}
