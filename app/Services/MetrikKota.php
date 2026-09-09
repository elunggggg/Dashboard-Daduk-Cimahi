<?php

namespace App\Services;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use Illuminate\Support\Collection;

/**
 * Metrik "se-Kota, periode terbaru" untuk blok bawaan Dashboard Publik yang
 * kini ikut mesin grid (bisa dipindah/disembunyikan lewat "Bagian Dashboard"):
 * KPI Jumlah Wajib KTP / Penduduk / Kepala Keluarga, dan Tren Penduduk /
 * Kepadatan antar periode. Dirender di server (bukan lagi dari api.dashboard
 * lewat dashboardApp() Alpine), jadi bagiannya jalan di halaman mana pun.
 */
class MetrikKota
{
    public function hitung(Collection $waktuList): array
    {
        $latest  = $waktuList->sortByDesc('tahun')->sortByDesc('semester')->first()
            ?? DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->first();
        $waktuId = $latest?->id;

        $gender    = $this->aggrKota('jenis_kelamin', $waktuId);
        $totalPend = (int) $gender->sum();
        $laki      = (int) $gender->get('Laki-laki', 0);
        $perempuan = (int) $gender->get('Perempuan', 0);
        $rasioLaki = $totalPend > 0 ? $laki / $totalPend : 0.5;

        $wajibKtp = (int) $this->aggrKota('kepemilikan_ktp', $waktuId)->get('Wajib KTP', 0);
        $kkTotal  = (int) $this->aggrKota('kepemilikan_kk', $waktuId)->get('Jumlah Kepala Keluarga', 0);
        $kkJk     = $this->aggrKota('kepala_keluarga_jk', $waktuId);

        // Wajib KTP tidak punya rincian L/P sendiri → DITAKSIR dari rasio kota.
        $kpiKtp = [
            'total' => $wajibKtp,
            'laki' => (int) round($wajibKtp * $rasioLaki),
            'perempuan' => (int) round($wajibKtp * (1 - $rasioLaki)),
            'estimasi' => true,
        ];
        $kpiPenduduk = ['total' => $totalPend, 'laki' => $laki, 'perempuan' => $perempuan, 'estimasi' => false];
        $kpiKk = [
            'total' => $kkTotal,
            'laki' => (int) $kkJk->get('Kepala Keluarga Laki-laki', 0),
            'perempuan' => (int) $kkJk->get('Kepala Keluarga Perempuan', 0),
            'estimasi' => false,
        ];

        // Tren se-Kota, seluruh periode (kepadatan = penduduk ÷ total luas kelurahan).
        $totalLuas   = (float) DimWilayah::kelurahan()->sum('luas_km2');
        $genderKatId = DimKategori::where('jenis_indikator', 'jenis_kelamin')->pluck('id');
        $periode     = DimWaktu::orderBy('tahun')->orderBy('semester')->get();
        $pendPerPeriode = $periode->map(fn (DimWaktu $w) => (int) DataAgregat::whereIn('kategori_id', $genderKatId)
            ->where('waktu_id', $w->id)->sum('jumlah'));

        return [
            'vars' => [
                'kpiKota'     => ['ktp' => $kpiKtp, 'penduduk' => $kpiPenduduk, 'kk' => $kpiKk],
                'periodeKota' => $latest?->label ?? '-',
            ],
            'charts' => [
                'kpi_ktp'      => ['labels' => ['Laki-laki', 'Perempuan'], 'values' => [$kpiKtp['laki'], $kpiKtp['perempuan']]],
                'kpi_penduduk' => ['labels' => ['Laki-laki', 'Perempuan'], 'values' => [$laki, $perempuan]],
                'kpi_kk'       => ['labels' => ['Laki-laki', 'Perempuan'], 'values' => [$kpiKk['laki'], $kpiKk['perempuan']]],
                'tren_kota'    => [
                    'labels'    => $periode->pluck('label')->values(),
                    'penduduk'  => $pendPerPeriode->values(),
                    'kepadatan' => $totalLuas > 0
                        ? $pendPerPeriode->map(fn ($p) => (int) round($p / $totalLuas))->values()
                        : $pendPerPeriode->map(fn () => 0)->values(),
                ],
            ],
        ];
    }

    private function aggrKota(string $jenis, ?int $waktuId): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->with('kategori')
            ->get()
            ->groupBy('kategori.label')
            ->map(fn ($rows) => (int) $rows->sum('jumlah'));
    }
}
