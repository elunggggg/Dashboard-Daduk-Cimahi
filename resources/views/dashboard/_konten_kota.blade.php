{{-- Blok "indikator kota" bawaan Dashboard Publik yang kini ikut mesin grid
     (bisa dipindah/disembunyikan/diurut lewat "Bagian Dashboard"). Se-Kota,
     periode terbaru. Data dari MetrikKota; chart dari gambarSemuaChart()
     (kunci: kpi_ktp / kpi_penduduk / kpi_kk / tren_kota). --}}
@php
    $__kartuKpi = [
        ['kunci' => 'kpi_kota_ktp',      'judul' => 'Jumlah Wajib KTP',        'data' => $kpiKota['ktp'],      'canvas' => 'chart-kpi-ktp',      'satuan' => 'jiwa wajib KTP', 'ikon' => 'bi-person-vcard text-amber-500', 'urutan' => 0],
        ['kunci' => 'kpi_kota_penduduk', 'judul' => 'Jumlah Penduduk',         'data' => $kpiKota['penduduk'], 'canvas' => 'chart-kpi-penduduk', 'satuan' => 'jiwa',          'ikon' => 'bi-people-fill text-blue-500',   'urutan' => 1],
        ['kunci' => 'kpi_kota_kk',       'judul' => 'Jumlah Kepala Keluarga',  'data' => $kpiKota['kk'],       'canvas' => 'chart-kpi-kk',       'satuan' => 'Kepala Keluarga','ikon' => 'bi-house-door-fill text-teal-600', 'urutan' => 2],
    ];
@endphp

@foreach ($__kartuKpi as $kpi)
    <x-seksi halaman="dashboard" kunci="{{ $kpi['kunci'] }}" judul="KPI Kota: {{ $kpi['judul'] }}" lebar="sepertiga" :urutan="$kpi['urutan']">
        <div class="card p-4 h-full">
            <div class="flex items-start justify-between mb-1">
                <h2 class="text-sm font-bold text-gray-900">{{ $kpi['judul'] }}</h2>
                <i class="bi {{ $kpi['ikon'] }}"></i>
            </div>
            @if ($kpi['data']['estimasi'] ?? false)
                <p class="text-[11px] text-amber-600 mb-1"><i class="bi bi-exclamation-triangle"></i> rincian L/P ditaksir</p>
            @else
                <p class="text-[11px] text-transparent mb-1 select-none">.</p>
            @endif
            <p class="text-3xl font-extrabold text-gray-900 tracking-tight text-center my-2">{{ number_format($kpi['data']['total'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-gray-400 text-center mb-3">{{ $kpi['satuan'] }} · {{ $periodeKota }}</p>
            <div class="h-36"><canvas id="{{ $kpi['canvas'] }}"></canvas></div>
            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5 text-gray-600"><span class="h-2 w-2 rounded-full" style="background:{{ config('dashboard-colors.laki') }}"></span>Laki-laki</span>
                    <strong class="text-gray-900">{{ number_format($kpi['data']['laki'], 0, ',', '.') }}</strong>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-1.5 text-gray-600"><span class="h-2 w-2 rounded-full" style="background:{{ config('dashboard-colors.perempuan') }}"></span>Perempuan</span>
                    <strong class="text-gray-900">{{ number_format($kpi['data']['perempuan'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </x-seksi>
@endforeach

<x-seksi halaman="dashboard" kunci="tren_penduduk_kota" judul="Tren Jumlah Penduduk (se-Kota)" lebar="separuh" :urutan="3">
    <div class="section-card h-full">
        <h2 class="section-title">Tren Jumlah Penduduk</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Se-Kota, antar periode</p>
        <div class="h-[300px]"><canvas id="chart-tren-penduduk"></canvas></div>
    </div>
</x-seksi>

<x-seksi halaman="dashboard" kunci="tren_kepadatan_kota" judul="Tren Kepadatan Penduduk (se-Kota)" lebar="separuh" :urutan="4">
    <div class="section-card h-full">
        <h2 class="section-title">Tren Kepadatan Penduduk</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Jiwa per km² — se-Kota, antar periode</p>
        <div class="h-[300px]"><canvas id="chart-tren-kepadatan"></canvas></div>
    </div>
</x-seksi>
