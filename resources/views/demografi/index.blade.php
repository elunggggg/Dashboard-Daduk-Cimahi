<x-layouts.public title="Demografi">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard
                    Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Demografi</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Data Demografi</h1>
            <p class="text-white/70 text-sm mt-1">
                Distribusi penduduk berdasarkan jenis kelamin, umur, status kawin, dan disabilitas
            </p>
        </div>
    </div>

    {{-- ── Phase 5: filter tanpa reload ──
         <x-filter-wilayah> cuma memancarkan event `filter-berubah` (lihat
         komponennya) — demografiApp() di bawah yang mendengarkan lalu fetch()
         ke route yang sama. Konten di bawah filter dirender server (partial
         demografi/_konten.blade.php) dan diganti utuh lewat x-html; 6 kanvas
         Chart.js-nya di-redraw terpisah oleh gambarSemuaChart(), dibaca dari
         kunci `charts` pada respons JSON. --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="demografiApp({
             waktu_id: @js($waktuId), wilayah_id: @js($wilayahId), kecamatan: @js($kecamatan),
             konten_html: @js((string) view('demografi._konten', [
                 'totalPenduduk' => $totalPenduduk, 'laki' => $laki, 'perempuan' => $perempuan,
                 'rasio' => $rasio, 'kepadatan' => $kepadatan, 'umurMedian' => $umurMedian,
                 'lpp' => $lpp, 'totalWna' => $totalWna, 'selectedWaktu' => $selectedWaktu,
                 'genderData' => $genderData, 'anakData' => $anakData, 'lansiaData' => $lansiaData,
                 'maritalData' => $maritalData, 'disabilData' => $disabilData,
                 'pctDisabilitas' => $pctDisabilitas, 'totalDisabilitas' => $totalDisabilitas, 'nonDisabilitas' => $nonDisabilitas,
                 'ageData' => $ageData, 'produktif' => $produktif, 'usiaMuda' => $usiaMuda, 'usiaTua' => $usiaTua,
                 'rasioKetergantungan' => $rasioKetergantungan, 'umurTunggalData' => $umurTunggalData,
                 'usia0' => $usia0, 'cbr' => $cbr, 'gfr' => $gfr, 'tfr' => $tfr, 'asfrData' => $asfrData,
                 'perkawinanKuData' => $perkawinanKuData, 'perkawinanKuJenis' => $perkawinanKuJenis,
                 'disabilitasKuData' => $disabilitasKuData, 'golDarKuData' => $golDarKuData,
                 'disabilitasPekerjaanData' => $disabilitasPekerjaanData, 'disabilitasUsklhData' => $disabilitasUsklhData,
             ])->render()),
             charts: {
                 gender:  { labels: @js($genderData->keys()->values()), values: @js($genderData->values()) },
                 anak:    { labels: @js($anakData->keys()->values()), values: @js($anakData->values()) },
                 lansia:  { labels: @js($lansiaData->keys()->values()), values: @js($lansiaData->values()) },
                 marital: { labels: @js($maritalData->keys()->values()), values: @js($maritalData->values()) },
                 disab:   { labels: @js($disabilData->keys()->values()), values: @js($disabilData->values()) },
                 piramida: {
                     labels: @js($ageLakiData->keys()->values()),
                     laki: @js($ageLakiData->values()),
                     perempuan: @js($agePerempuanData->values()),
                 },
                 total_penduduk: @js($totalPenduduk),
             },
         })"
         @filter-berubah.window="muat($event.detail)"
    >

        {{-- Filter --}}
        <x-filter-wilayah action="{{ route('demografi.index') }}" :kecamatanList="$kecamatanList" :wilayahList="$wilayahList" :waktuList="$waktuList"
            :kecamatan="$kecamatan" :wilayahId="$wilayahId" :waktuId="$waktuId" />

        <div x-show="loading" class="space-y-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <x-skeleton-card :chart="false" :rows="2" />
                <x-skeleton-card :chart="false" :rows="2" />
                <x-skeleton-card :chart="false" :rows="2" />
                <x-skeleton-card :chart="false" :rows="2" />
                <x-skeleton-card :chart="false" :rows="2" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <x-skeleton-card :rows="3" /><x-skeleton-card :rows="3" /><x-skeleton-card :rows="3" />
            </div>
        </div>

        <div x-show="!loading" x-cloak x-html="kontenHtml" class="space-y-6"></div>

    </div>

    <x-slot:scripts>
        <script>
            // ── x-data utama halaman Demografi: filter kecamatan/kelurahan/
            // periode tanpa reload (Phase 5). Konten (tabel, KPI, komponen
            // "rincian-indikator") dirender server & diganti lewat x-html;
            // 6 kanvas Chart.js di-redraw manual lewat gambarSemuaChart(),
            // fungsi yang sama dipakai untuk kunjungan pertama (init())
            // maupun tiap fetch.
            function demografiApp(seed) {
                return {
                    loading: false,
                    kontenHtml: seed.konten_html,

                    init() {
                        // Kanvas ada DI DALAM kontenHtml (x-html) — pada saat
                        // init() ini dipanggil Alpine belum sempat menyuntikkan
                        // markup-nya ke DOM, jadi tunggu satu tick dulu.
                        this.$nextTick(() => this.gambarSemuaChart(seed.charts));
                    },

                    async muat(filter) {
                        this.loading = true;

                        const params = new URLSearchParams();
                        if (filter.kecamatan) params.set('kecamatan', filter.kecamatan);
                        if (filter.wilayah_id) params.set('wilayah_id', filter.wilayah_id);
                        if (filter.waktu_id) params.set('waktu_id', filter.waktu_id);

                        const url = '{{ route('demografi.index') }}' + (params.toString() ? '?' + params.toString() : '');
                        window.history.pushState({}, '', url);

                        try {
                            const res = await fetch(url, {
                                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            const json = await res.json();

                            this.kontenHtml = json.konten_html;
                            this.loading = false;
                            await this.$nextTick();
                            this.gambarSemuaChart(json.charts);
                        } catch (e) {
                            console.error('Gagal memuat data Demografi', e);
                            this.loading = false;
                        }
                    },

                    gambarSemuaChart(d) {
                        const fmt = window.formatAngka;
                        const W = window.DadukColors;
                        const K = window.DadukKategori;

                        // x-html mengganti SELURUH markup konten (termasuk elemen
                        // <canvas>) setiap fetch — Chart.js tetap menyimpan
                        // instance lamanya di registry internal kalau tidak
                        // di-destroy dulu (Chart.getChart(id) mencocokkan lewat
                        // string id, bukan identitas elemen).
                        function hancurkanJikaAda(canvasId) {
                            const lama = window.Chart.getChart(canvasId);
                            if (lama) lama.destroy();
                        }

                        function donutCenter(canvasId, seri, subtext) {
                            const canvas = document.getElementById(canvasId);
                            if (!canvas) return;
                            hancurkanJikaAda(canvasId);
                            const total = seri.values.reduce((a, b) => a + b, 0);
                            new Chart(canvas, {
                                type: 'doughnut',
                                data: {
                                    labels: seri.labels,
                                    datasets: [{
                                        data: seri.values,
                                        backgroundColor: seri.labels.map((l) => l === 'Laki-laki' ? W.laki : (l === 'Perempuan' ? W.perempuan : K[0])),
                                        borderWidth: 0,
                                        hoverOffset: 4,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '68%',
                                    layout: { padding: 16 },
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                        centerText: { display: true, text: fmt(total), subtext },
                                        datalabels: {
                                            display: true, anchor: 'end', align: 'end', offset: 6,
                                            color: '#374151', font: { size: 10, weight: '600' },
                                            formatter: (v) => fmt(v),
                                        },
                                    },
                                },
                            });
                        }

                        // ── Jenis Kelamin, Jumlah Anak (0-14), Jumlah Penduduk Lansia (65+) — donut ──
                        donutCenter('chart-gender', d.gender, 'jiwa');
                        donutCenter('chart-anak', d.anak, 'anak');
                        donutCenter('chart-lansia', d.lansia, 'lansia');

                        // ── Piramida Penduduk — butterfly horizontal bar ─────────
                        const canvasPiramida = document.getElementById('chart-piramida');
                        if (canvasPiramida) {
                            hancurkanJikaAda('chart-piramida');
                            new Chart(canvasPiramida, {
                                type: 'bar',
                                data: {
                                    labels: d.piramida.labels,
                                    datasets: [
                                        { label: 'Laki-laki', data: d.piramida.laki.map((v) => -v), backgroundColor: W.laki, borderRadius: 3, borderSkipped: false },
                                        { label: 'Perempuan', data: d.piramida.perempuan, backgroundColor: W.perempuan, borderRadius: 3, borderSkipped: false },
                                    ],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    layout: { padding: { left: 30, right: 30 } },
                                    plugins: {
                                        legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
                                        tooltip: {
                                            callbacks: {
                                                label: (c) => {
                                                    const nilai = Math.abs(c.raw);
                                                    const total = d.total_penduduk;
                                                    const persen = total > 0 ? window.formatPersen(nilai / total * 100) : '';
                                                    return `${c.dataset.label}: ${fmt(nilai)} jiwa${persen ? ` (${persen})` : ''}`;
                                                },
                                            },
                                        },
                                        datalabels: {
                                            display: true, anchor: 'end', align: 'end', clamp: true,
                                            color: '#374151', font: { size: 9, weight: '600' },
                                            formatter: (v) => fmt(Math.abs(v)),
                                        },
                                    },
                                    scales: {
                                        x: { stacked: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 9 }, callback: (v) => fmt(Math.abs(v)) } },
                                        y: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                                    },
                                },
                            });
                        }

                        // ── Marital — horizontal bar ─────────────────────────────
                        const canvasMarital = document.getElementById('chart-marital');
                        if (canvasMarital) {
                            hancurkanJikaAda('chart-marital');
                            const maritalTotal = d.marital.values.reduce((a, b) => a + b, 0);
                            new Chart(canvasMarital, {
                                type: 'bar',
                                data: {
                                    labels: d.marital.labels,
                                    datasets: [{
                                        data: d.marital.values,
                                        backgroundColor: d.marital.labels.map((_, i) => K[i % K.length]),
                                        borderRadius: 6,
                                        borderSkipped: false,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    plugins: {
                                        legend: { display: false },
                                        tooltip: { callbacks: { label: window.tooltipPersenLabel(maritalTotal) } },
                                        datalabels: {
                                            display: true, anchor: 'end', align: 'end', clamp: true,
                                            color: '#374151', font: { size: 9, weight: '600' },
                                            formatter: (v) => fmt(v),
                                        },
                                    },
                                    scales: {
                                        x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                                        y: { grid: { display: false }, ticks: { font: { size: 10 } } },
                                    },
                                },
                            });
                        }

                        // ── Disabilitas — donut ──────────────────────────────────
                        const canvasDisab = document.getElementById('chart-disab');
                        if (canvasDisab) {
                            hancurkanJikaAda('chart-disab');
                            const disabTotal = d.disab.values.reduce((a, b) => a + b, 0);
                            new Chart(canvasDisab, {
                                type: 'doughnut',
                                data: {
                                    labels: d.disab.labels,
                                    datasets: [{
                                        data: d.disab.values,
                                        backgroundColor: d.disab.labels.map((_, i) => K[i % K.length]),
                                        borderWidth: 0,
                                        hoverOffset: 4,
                                    }],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    cutout: '60%',
                                    layout: { padding: 16 },
                                    plugins: {
                                        legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 10, font: { size: 9 } } },
                                        tooltip: { callbacks: { label: window.tooltipPersenLabel(disabTotal) } },
                                        centerText: { display: true, text: fmt(disabTotal), subtext: 'jiwa' },
                                        datalabels: {
                                            display: true, anchor: 'end', align: 'end', offset: 4,
                                            color: '#374151', font: { size: 9, weight: '600' },
                                            formatter: (v) => fmt(v),
                                        },
                                    },
                                },
                            });
                        }
                    },
                };
            }
        </script>
    </x-slot:scripts>

</x-layouts.public>
