<x-layouts.public title="Sosial">

    <div class="bg-brand-900 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Sosial</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Data Sosial</h1>
            <p class="text-white/70 text-sm mt-1">
                Pendidikan, pekerjaan, agama, dan kepemilikan dokumen kependudukan
            </p>
        </div>
    </div>

    {{-- Konten (KPI, tabel, progress bar) dirender langsung oleh server lewat
         partial sosial/_konten.blade.php — lihat @include di bawah — jadi data
         selalu tampil meski JS mati. <x-filter-wilayah> memancarkan event
         `filter-berubah`; sosialApp() menangkapnya lalu reload halaman dengan
         query param. 14 kanvas Chart.js digambar gambarSemuaChart() dari kunci
         `charts` pada seed x-data. --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="sosialApp({
             waktu_id: @js($waktuId), wilayah_id: @js($wilayahId), kecamatan: @js($kecamatan),
             charts: {
                 edu: { labels: @json($pendidikanData->keys()->values()), values: @json($pendidikanData->values()) },
                 job: { labels: @json($pekerjaanData->keys()->values()), values: @json($pekerjaanData->values()) },
                 usia_sekolah: { labels: @json($usiaSekolahData->keys()->values()), values: @json($usiaSekolahData->values()) },
                 agama: { labels: @json($agamaData->keys()->values()), values: @json($agamaData->values()) },
                 ktp_status: { labels: @json($ktpStatusData->keys()->values()), values: @json($ktpStatusData->values()) },
                 kk_status: { labels: @json($kkStatusData->keys()->values()), values: @json($kkStatusData->values()) },
                 goldar: { labels: @json($golonganDarahData->keys()->values()), values: @json($golonganDarahData->values()) },
                 shbkel: { labels: @json($shbkelData->keys()->values()), values: @json($shbkelData->values()) },
                 kia: { labels: ['Memiliki KIA', 'Belum Memiliki KIA'], values: [@json($kiaData->get('Memiliki KIA', 0)), @json($kiaData->get('Belum Memiliki KIA', 0))] },
                 akta_lahir: { labels: ['Memiliki', 'Belum Memiliki'], values: [@json($aktaLahirData->get('Memiliki Akta Lahir', 0)), @json($aktaLahirData->get('Belum Memiliki Akta Lahir', 0))] },
                 kk_jk: { labels: @json($kepalaKeluargaJkData->keys()->values()), values: @json($kepalaKeluargaJkData->values()) },
                 akta_lahir_kelurahan: @json($aktaLahirKelurahanData),
                 kia_kelurahan: @json($kiaKelurahanData),
                 ktp_kelurahan: @json($ktpKelurahanData),
             },
         })"
         @filter-berubah.window="muat($event.detail)"
    >

        <x-filter-wilayah
            action="{{ route('sosial.index') }}"
            :kecamatanList="$kecamatanList"
            :wilayahList="$wilayahList"
            :waktuList="$waktuList"
            :kecamatan="$kecamatan"
            :wilayahId="$wilayahId"
            :waktuId="$waktuId"
        />

        {{-- Konten dirender LANGSUNG di server (bukan lewat x-html) supaya data
             selalu tampil walau Alpine/JS gagal dimuat. Filter di atas tetap
             berfungsi lewat reload halaman biasa (lihat muat() di bawah). --}}
        <div class="space-y-6">
            @include('sosial._konten')
        </div>

    </div>

    <x-slot:scripts>
    <script>
        // ── x-data utama halaman Sosial: filter kecamatan/kelurahan/periode
        // tanpa reload (Phase 5). Konten (tabel, progress bar, komponen
        // komponen "rincian-indikator") dirender server & diganti lewat x-html;
        // 14 kanvas Chart.js di-redraw manual lewat gambarSemuaChart(), sama
        // fungsi dipakai untuk kunjungan pertama (init()) maupun tiap fetch.
        function sosialApp(seed) {
            return {
                loading: false,

                init() {
                    // Kanvas Chart.js ada di dalam partial sosial/_konten yang
                    // sudah dirender server — tunggu satu tick supaya elemennya
                    // pasti sudah ada di DOM sebelum digambar.
                    this.$nextTick(() => this.gambarSemuaChart(seed.charts));
                },

                // Filter "Tampilkan"/"Reset" memancarkan `filter-berubah`; di sini
                // cukup reload halaman dengan query param — konten & chart ikut
                // dirender ulang oleh server. Sederhana dan tahan banting.
                muat(filter) {
                    this.loading = true;
                    const params = new URLSearchParams();
                    if (filter.kecamatan) params.set('kecamatan', filter.kecamatan);
                    if (filter.wilayah_id) params.set('wilayah_id', filter.wilayah_id);
                    if (filter.waktu_id) params.set('waktu_id', filter.waktu_id);
                    const qs = params.toString();
                    window.location = '{{ route('sosial.index') }}' + (qs ? '?' + qs : '');
                },

                gambarSemuaChart(d) {
                    const fmt = window.formatAngka;
                    const W = window.DadukColors;
                    const K = window.DadukKategori;

                    // x-html mengganti SELURUH markup konten (termasuk elemen
                    // <canvas>) setiap fetch — node lama benar-benar lenyap dari
                    // DOM, tapi Chart.js tetap menyimpan instance lamanya di
                    // registry internal (Chart.getChart(id) mencocokkan lewat
                    // string id, bukan identitas elemen) kalau tidak di-destroy
                    // dulu. Tanpa ini, filter "Cari kategori" di
                    // komponen "rincian-indikator" bisa memanipulasi chart HANTU yang
                    // sudah tidak terlihat, bukan yang baru digambar.
                    function hancurkanJikaAda(canvasId) {
                        const lama = window.Chart.getChart(canvasId);
                        if (lama) lama.destroy();
                    }

                    function barChart(canvasId, seri, opts = {}) {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;
                        hancurkanJikaAda(canvasId);
                        const total = seri.values.reduce((a, b) => a + b, 0);
                        new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels: seri.labels,
                                datasets: [{
                                    data: seri.values,
                                    backgroundColor: opts.warna ?? seri.labels.map((_, i) => K[i % K.length]),
                                    borderRadius: 4,
                                    borderSkipped: false,
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                    datalabels: { display: true, anchor: 'end', align: 'end', clamp: true, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                                },
                                scales: {
                                    x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                                    y: { grid: { display: false }, ticks: { font: { size: 9 } } },
                                },
                            },
                        });
                    }

                    function donutCenter(canvasId, seri, subtext, warna) {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;
                        hancurkanJikaAda(canvasId);
                        const total = seri.values.reduce((a, b) => a + b, 0);
                        new Chart(canvas, {
                            type: 'doughnut',
                            data: { labels: seri.labels, datasets: [{ data: seri.values, backgroundColor: warna, borderWidth: 0, hoverOffset: 4 }] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '68%',
                                layout: { padding: 16 },
                                plugins: {
                                    legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 10, font: { size: 10 } } },
                                    tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                    centerText: { display: true, text: fmt(total), subtext },
                                    datalabels: { display: true, anchor: 'end', align: 'end', offset: 6, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                                },
                            },
                        });
                    }

                    // Wajib X per kelurahan — stacked horizontal bar (Memiliki hijau vs Belum merah muda).
                    function stackedKelurahan(canvasId, data) {
                        const canvas = document.getElementById(canvasId);
                        if (!canvas) return;
                        hancurkanJikaAda(canvasId);
                        const labels = Object.keys(data);
                        const memiliki = labels.map((k) => data[k].memiliki ?? 0);
                        const belum = labels.map((k) => data[k].belum ?? 0);
                        new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels,
                                datasets: [
                                    { label: 'Memiliki', data: memiliki, backgroundColor: W.positif, borderRadius: 3, borderSkipped: false },
                                    { label: 'Belum', data: belum, backgroundColor: '#FBCFE8', borderRadius: 3, borderSkipped: false },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                indexAxis: 'y',
                                plugins: {
                                    legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } } },
                                    tooltip: {
                                        callbacks: {
                                            label: (c) => {
                                                const t = memiliki[c.dataIndex] + belum[c.dataIndex];
                                                return `${c.dataset.label}: ${fmt(c.raw)} (${window.formatPersen(t > 0 ? c.raw / t * 100 : 0)})`;
                                            },
                                        },
                                    },
                                },
                                scales: {
                                    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                                    y: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 } } },
                                },
                            },
                        });
                    }

                    // ── Pendidikan, Pekerjaan, Usia Sekolah, Agama, KTP, KK, Golongan Darah, SHBKEL ──
                    barChart('chart-edu', d.edu);
                    barChart('chart-job', d.job);
                    barChart('chart-usia-sekolah', d.usia_sekolah);
                    barChart('chart-agama', d.agama);
                    barChart('chart-ktp-status', d.ktp_status);
                    barChart('chart-kk-status', d.kk_status);
                    barChart('chart-goldar', d.goldar);
                    barChart('chart-shbkel', d.shbkel);

                    // ── KIA & Akta Lahir — donut Memiliki vs Belum ───────────
                    donutCenter('chart-kia', d.kia, 'anak', [W.positif, '#FBCFE8']);
                    donutCenter('chart-akta-lahir', d.akta_lahir, 'jiwa', [W.positif, '#FBCFE8']);

                    // ── Kepala Keluarga — donut kecil, L/P ───────────────────
                    donutCenter('chart-kk-jk', d.kk_jk, 'KK', [W.laki, W.perempuan]);

                    // ── Wajib Akta Lahir / KIA / KTP per Kelurahan ───────────
                    stackedKelurahan('chart-akta-lahir-kelurahan', d.akta_lahir_kelurahan);
                    stackedKelurahan('chart-kia-kelurahan', d.kia_kelurahan);
                    stackedKelurahan('chart-ktp-kelurahan', d.ktp_kelurahan);
                },
            };
        }
    </script>
    </x-slot:scripts>

</x-layouts.public>
