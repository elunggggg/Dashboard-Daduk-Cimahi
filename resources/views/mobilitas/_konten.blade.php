{{-- Bagian-bagian Mobilitas — dirender DI DALAM .seksi-grid (dashboard/_grid)
     bersama Demografi/Sosial/Dashboard. Redraw Chart.js: gambarSemuaChart()
     di dashboard/_skrip (kunci `datang`, `pindah`, `trend`). Angka per-kelurahan
     & tren SENGAJA tidak ikut filter wilayah — hanya periode. --}}

<x-seksi halaman="mobilitas" kunci="kpi_mobilitas" judul="KPI Ringkas Mobilitas (Pendatang / Pindah / Saldo / Rasio)" lebar="penuh" :urutan="0">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="kpi-card">
            <div class="kpi-icon bg-green-100 text-green-700"><i class="bi bi-arrow-down-circle text-xl"></i></div>
            <div>
                <p class="text-xs font-medium text-gray-500">Total Pendatang</p>
                <p class="text-2xl font-extrabold text-gray-900">{{ number_format($totalDatang, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">jiwa masuk · {{ $periodeLabelMobilitas }}</p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-red-100 text-red-700"><i class="bi bi-arrow-up-circle text-xl"></i></div>
            <div>
                <p class="text-xs font-medium text-gray-500">Total Pindah Keluar</p>
                <p class="text-2xl font-extrabold text-gray-900">{{ number_format($totalPindah, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">jiwa keluar · {{ $periodeLabelMobilitas }}</p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon {{ $saldo >= 0 ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700' }}">
                <i class="bi {{ $saldo >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }}"></i>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Saldo Migrasi</p>
                <p class="text-2xl font-extrabold {{ $saldo >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                    {{ ($saldo >= 0 ? '+' : '') . number_format($saldo, 0, ',', '.') }}
                </p>
                <p class="text-xs text-gray-400">{{ $saldo >= 0 ? 'surplus migrasi' : 'defisit migrasi' }}</p>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-amber-100 text-amber-700"><i class="bi bi-arrow-left-right text-xl"></i></div>
            <div>
                <p class="text-xs font-medium text-gray-500">Rasio Pindah-Datang</p>
                <p class="text-2xl font-extrabold text-gray-900">{{ number_format($rasioPindahDatang, 1, ',', '.') }}</p>
                <p class="text-xs text-gray-400">per 100 pendatang</p>
            </div>
        </div>
    </div>
</x-seksi>

<x-seksi halaman="mobilitas" kunci="pendatang_kelurahan" judul="Pendatang per Kelurahan" lebar="separuh" :urutan="10">
    <div class="section-card h-full">
        <h2 class="section-title">Pendatang per Kelurahan</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">{{ $periodeLabelMobilitas }} — diurutkan terbanyak</p>
        <div class="h-[400px]"><canvas id="chart-datang"></canvas></div>
        <x-rincian-indikator :data="$datangData" :total="$totalDatang" chart-id="chart-datang" />
    </div>
</x-seksi>

<x-seksi halaman="mobilitas" kunci="pindah_kelurahan" judul="Pindah Keluar per Kelurahan" lebar="separuh" :urutan="20">
    <div class="section-card h-full">
        <h2 class="section-title">Pindah Keluar per Kelurahan</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">{{ $periodeLabelMobilitas }} — diurutkan terbanyak</p>
        <div class="h-[400px]"><canvas id="chart-pindah"></canvas></div>
        <x-rincian-indikator :data="$pindahData" :total="$totalPindah" chart-id="chart-pindah" />
    </div>
</x-seksi>

<x-seksi halaman="mobilitas" kunci="tren_antar_periode" judul="Tren Mobilitas Antar Periode" lebar="penuh" :urutan="30">
    <div class="section-card" x-data="{ pilihPeriode: '' }">
        <h2 class="section-title">Tren Mobilitas Antar Periode</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Perbandingan jumlah datang vs pindah setiap semester (seluruh riwayat)</p>
        <div class="h-[300px]"><canvas id="chart-trend"></canvas></div>

        @if ($trendDatang->count() > 1)
            <div class="mt-2 flex gap-4 text-xs">
                <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block" style="background:#10B981"></span>Pendatang</span>
                <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block" style="background:#EF4444"></span>Pindah Keluar</span>
            </div>
        @endif

        <div class="mt-3 pt-3 border-t border-gray-100">
            @if ($trendDatang->count() > 1)
                <div class="flex items-center gap-2 mb-2">
                    <label class="text-[11px] font-medium text-gray-500 flex items-center gap-1 flex-shrink-0">
                        <i class="bi bi-funnel text-gray-400"></i> Cari periode
                    </label>
                    <select class="form-select text-xs py-1 w-auto" x-model="pilihPeriode">
                        <option value="">Semua periode</option>
                        @foreach ($trendDatang as $t)
                            <option value="{{ $t['label'] }}">{{ $t['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="border border-gray-100 rounded-lg overflow-hidden divide-y divide-gray-100">
                @foreach ($trendDatang as $i => $t)
                    <div x-show="pilihPeriode === '' || pilihPeriode === @js($t['label'])"
                        class="grid grid-cols-3 gap-3 px-3 py-1.5 text-xs items-center">
                        <span class="text-gray-600 truncate">{{ $t['label'] }}</span>
                        <span class="font-medium" style="color:#10B981">Datang: {{ number_format($t['total'], 0, ',', '.') }}</span>
                        <span class="font-medium" style="color:#EF4444">Pindah: {{ number_format($trendPindah[$i]['total'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-seksi>
