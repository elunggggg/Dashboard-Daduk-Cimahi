<x-layouts.public title="Mobilitas">

    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Mobilitas</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Mobilitas Penduduk</h1>
            <p class="text-white/70 text-sm mt-1">
                Pergerakan penduduk masuk (datang) dan keluar (pindah) Kota Cimahi per semester
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Filter periode — halaman ini satu-satunya halaman publik yang punya
             filter periode berdiri sendiri, dan hanya berguna bila ada lebih dari
             satu periode untuk dibandingkan/digabung. Dengan 0 atau 1 periode
             kontrolnya tidak bisa mengubah apa pun, jadi tidak dirender. --}}
        @if($waktuList->count() > 1)
            <div class="card p-4">
                <form method="GET" action="{{ route('mobilitas.index') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="form-label">Periode</label>
                        <select name="waktu_id" class="form-select" onchange="this.form.submit()">
                            {{-- Boleh di sini, tidak di Demografi/Sosial: angka mobilitas
                                 berupa arus, jadi gabungan antar semester = total
                                 perpindahan, bukan orang yang terhitung dua kali. --}}
                            <option value="" @selected($waktuId === null)>Semua Periode</option>
                            @foreach($waktuList as $w)
                                <option value="{{ $w->id }}" @selected($w->id == $waktuId)>{{ $w->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Terapkan</button>
                </form>

                @if($waktuId === null)
                    <p class="mt-3 flex items-start gap-1.5 text-[11px] text-gray-500">
                        <i class="bi bi-info-circle-fill mt-0.5"></i>
                        <span>
                            Menampilkan <strong>gabungan {{ $waktuList->count() }} periode</strong> — total
                            perpindahan sepanjang periode tersebut, bukan keadaan pada satu titik waktu.
                        </span>
                    </p>
                @endif
            </div>
        @endif

        {{-- KPI strip --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="kpi-card">
                <div class="kpi-icon bg-green-100 text-green-700"><i class="bi bi-arrow-down-circle text-xl"></i></div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Pendatang</p>
                    <p class="text-2xl font-extrabold text-gray-900">{{ number_format($totalDatang,0,',','.') }}</p>
                    <p class="text-xs text-gray-400">jiwa masuk · {{ $selectedWaktu->label ?? ($waktuId === null ? 'Semua Periode' : '-') }}</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon bg-red-100 text-red-700"><i class="bi bi-arrow-up-circle text-xl"></i></div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Total Pindah Keluar</p>
                    <p class="text-2xl font-extrabold text-gray-900">{{ number_format($totalPindah,0,',','.') }}</p>
                    <p class="text-xs text-gray-400">jiwa keluar · {{ $selectedWaktu->label ?? ($waktuId === null ? 'Semua Periode' : '-') }}</p>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon {{ $saldo >= 0 ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700' }}">
                    <i class="bi {{ $saldo >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} text-xl"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500">Saldo Migrasi</p>
                    <p class="text-2xl font-extrabold {{ $saldo >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                        {{ $saldo >= 0 ? '+' : '' }}{{ number_format($saldo,0,',','.') }}
                    </p>
                    <p class="text-xs text-gray-400">{{ $saldo >= 0 ? 'surplus' : 'defisit' }} migrasi</p>
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

        {{-- Distribusi per kelurahan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="section-card">
                <h2 class="section-title">Pendatang per Kelurahan</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">{{ $selectedWaktu->label ?? ($waktuId === null ? 'Semua Periode' : '-') }} — diurutkan terbanyak</p>
                <div class="h-[400px]"><canvas id="chart-datang"></canvas></div>
                <x-rincian-indikator :data="$datangData" :total="$totalDatang" chart-id="chart-datang" />
            </div>
            <div class="section-card">
                <h2 class="section-title">Pindah Keluar per Kelurahan</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">{{ $selectedWaktu->label ?? ($waktuId === null ? 'Semua Periode' : '-') }} — diurutkan terbanyak</p>
                <div class="h-[400px]"><canvas id="chart-pindah"></canvas></div>
                <x-rincian-indikator :data="$pindahData" :total="$totalPindah" chart-id="chart-pindah" />
            </div>
        </div>

        {{-- Tren antar periode — line kalau >1 periode, bar kalau cuma 1 --}}
        <div class="section-card">
            <h2 class="section-title">Tren Mobilitas Antar Periode</h2>
            <p class="text-xs text-gray-400 -mt-2 mb-3">Perbandingan jumlah datang vs pindah setiap semester</p>
            <div class="h-[300px]"><canvas id="chart-trend"></canvas></div>
            @if ($trendDatang->count() > 1)
                <div class="mt-2 flex gap-4 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block" style="background:#10B981"></span>Pendatang</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-0.5 inline-block" style="background:#EF4444"></span>Pindah Keluar</span>
                </div>
            @endif

            {{-- Rincian angka per periode + filter (filter cuma berguna kalau ada >1 periode) --}}
            <div class="mt-3 pt-3 border-t border-gray-100" x-data="{ pilihPeriode: '' }">
                @if ($trendDatang->count() > 1)
                    <div class="flex items-center gap-2 mb-2">
                        <label class="text-[11px] font-medium text-gray-500 flex items-center gap-1 flex-shrink-0">
                            <i class="bi bi-funnel text-gray-400"></i> Cari periode
                        </label>
                        <select class="form-select text-xs py-1" x-model="pilihPeriode"
                            @change="window._filterTrend && window._filterTrend(pilihPeriode)">
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
                            <span class="font-medium" style="color:#059669">Datang: {{ number_format($t['total'], 0, ',', '.') }}</span>
                            <span class="font-medium" style="color:#DC2626">Pindah: {{ number_format($trendPindah[$i]['total'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    <x-slot:scripts>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const fmt = window.formatAngka;
        const W = window.DadukColors;

        // ── Pendatang / Pindah per kelurahan — horizontal bar ────
        const datangLabels = @json($datangData->keys()->values());
        const datangValues = @json($datangData->values());
        const datangTotal  = datangValues.reduce((a, b) => a + b, 0);
        new Chart(document.getElementById('chart-datang'), {
            type: 'bar',
            data: { labels: datangLabels, datasets: [{ data: datangValues, backgroundColor: W.positif, borderRadius: 4, borderSkipped: false }] },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: window.tooltipPersenLabel(datangTotal) } },
                    datalabels: { display: true, anchor: 'end', align: 'end', clamp: true, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                    y: { grid: { display: false }, ticks: { font: { size: 9 } } },
                },
            },
        });

        const pindahLabels = @json($pindahData->keys()->values());
        const pindahValues = @json($pindahData->values());
        const pindahTotal  = pindahValues.reduce((a, b) => a + b, 0);
        new Chart(document.getElementById('chart-pindah'), {
            type: 'bar',
            data: { labels: pindahLabels, datasets: [{ data: pindahValues, backgroundColor: W.negatif, borderRadius: 4, borderSkipped: false }] },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: window.tooltipPersenLabel(pindahTotal) } },
                    datalabels: { display: true, anchor: 'end', align: 'end', clamp: true, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                    y: { grid: { display: false }, ticks: { font: { size: 9 } } },
                },
            },
        });

        // ── Tren antar periode — line kalau >1 periode, bar kalau cuma 1 ──
        const trendLabelsAsli = @json($trendDatang->pluck('label')->values());
        const trendDtgAsli    = @json($trendDatang->pluck('total')->values());
        const trendPdhAsli    = @json($trendPindah->pluck('total')->values());
        const trendChartType  = trendLabelsAsli.length > 1 ? 'line' : 'bar';

        const trendDatasets = trendChartType === 'line'
            ? [
                { label: 'Pendatang', data: trendDtgAsli, borderColor: W.positif, backgroundColor: 'rgba(16,185,129,.08)', tension: .35, fill: true, pointRadius: 5, pointHoverRadius: 7 },
                { label: 'Pindah Keluar', data: trendPdhAsli, borderColor: W.negatif, backgroundColor: 'rgba(239,68,68,.06)', tension: .35, fill: true, pointRadius: 5, pointHoverRadius: 7 },
            ]
            : [
                { label: 'Pendatang', data: trendDtgAsli, backgroundColor: W.positif, borderRadius: 4, borderSkipped: false },
                { label: 'Pindah Keluar', data: trendPdhAsli, backgroundColor: W.negatif, borderRadius: 4, borderSkipped: false },
            ];

        window._chartTrend = new Chart(document.getElementById('chart-trend'), {
            type: trendChartType,
            data: { labels: trendLabelsAsli, datasets: trendDatasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: trendChartType === 'bar', position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
                    tooltip: { mode: 'index', intersect: false, callbacks: { label: (c) => `${c.dataset.label}: ${fmt(c.raw)} jiwa` } },
                    datalabels: { display: true, align: trendChartType === 'line' ? 'top' : 'end', anchor: trendChartType === 'bar' ? 'end' : undefined, color: '#374151', font: { size: 8, weight: '600' }, formatter: (v) => fmt(v) },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color: '#f0f0f0' }, beginAtZero: true, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                },
            },
        });

        // Filter "Cari periode" (di bawah chart Tren) juga menyaring chart-nya,
        // bukan cuma tabel rincian di bawahnya.
        window._filterTrend = function (periodeTerpilih) {
            const chart = window._chartTrend;
            if (!chart) return;
            if (!periodeTerpilih) {
                chart.data.labels = trendLabelsAsli;
                chart.data.datasets[0].data = trendDtgAsli;
                chart.data.datasets[1].data = trendPdhAsli;
            } else {
                const idx = trendLabelsAsli.indexOf(periodeTerpilih);
                chart.data.labels = [periodeTerpilih];
                chart.data.datasets[0].data = [trendDtgAsli[idx]];
                chart.data.datasets[1].data = [trendPdhAsli[idx]];
            }
            chart.update();
        };
    });
    </script>
    </x-slot:scripts>

</x-layouts.public>
