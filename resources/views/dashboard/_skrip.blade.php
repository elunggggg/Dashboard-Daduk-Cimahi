<script>
    // ── Semua halaman ber-grid (Demografi/Sosial/Mobilitas/Dashboard Publik) ──
    // kini merender kontennya LANGSUNG di server (Blade raw-echo), BUKAN
    // lewat x-html/x-data Alpine. Dulu ada `dashboardGridApp()` sebagai
    // shell Alpine tipis buat itu (dan sebelumnya lagi, mesin AJAX penuh) —
    // keduanya DIHAPUS 2026-09-11 karena `x-html` menyuntikkan bagian yang
    // berisi widget ber-x-data ASYNC (mis. "Perbandingan Antar Periode")
    // TERBUKTI membuat state widget itu RANDOM RESET setiap kali properti
    // reaktifnya berubah: `Alpine.initTree()` atas konten baru berjalan
    // SINKRON di dalam effect x-html itu sendiri, sehingga setiap pembacaan
    // properti reaktif SELAMA inisialisasi widget (mis. `init()`/`muat()`
    // sebelum `await` pertama) salah tertaut sebagai dependensi effect
    // x-html tsb — akibatnya widget itu (dan seluruh `.seksi-grid`) DIBANGUN
    // ULANG DARI STRING STATIS ASLINYA setiap kali widget itu sendiri
    // "berubah", menghapus interaksi pengguna (kelihatan seolah dropdown
    // "tidak berpengaruh"). Merender langsung sebagai HTML biasa (bagian dari
    // muatan HALAMAN AWAL, bukan disuntikkan lewat efek reaktif belakangan)
    // menghindari seluruh kelas bug ini — lihat resources/views/dashboard-
    // publik/index.blade.php untuk pemanggilan window.gambarSemuaChartDaduk()
    // versi halaman itu.

    // Menggambar SELURUH chart di grid (Demografi/Sosial/Mobilitas/Dashboard
    // Publik) dari payload `charts`. Tiap kanvas hanya digambar kalau elemennya
    // ADA di DOM (bagian yang disembunyikan / dipindah ke halaman lain tidak
    // punya kanvas → dilewati). Dipanggil sekali saat halaman selesai dimuat.
    window.gambarSemuaChartDaduk = function (d) {
                const fmt = window.formatAngka;
                const W = window.DadukColors;
                const K = window.DadukKategori;

                function hancurkanJikaAda(id) {
                    const lama = window.Chart.getChart(id);
                    if (lama) lama.destroy();
                }

                // ── Donut angka-di-tengah, warna kategori ──
                function donutCenter(id, seri, subtext, warna) {
                    const c = document.getElementById(id);
                    if (!c || !seri) return;
                    hancurkanJikaAda(id);
                    const total = seri.values.reduce((a, b) => a + b, 0);
                    new Chart(c, {
                        type: 'doughnut',
                        data: { labels: seri.labels, datasets: [{ data: seri.values, backgroundColor: warna ?? seri.labels.map((_, i) => K[i % K.length]), borderWidth: 0, hoverOffset: 4 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '68%', layout: { padding: 16 },
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 10, font: { size: 10 } } },
                                tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                centerText: { display: true, text: fmt(total), subtext },
                                datalabels: { display: true, anchor: 'end', align: 'end', offset: 6, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                            },
                        },
                    });
                }

                // ── Donut L/P (Jenis Kelamin, Anak, Lansia, Kepala Keluarga) ──
                // Warna dicocokkan lewat kata "perempuan"/"laki" DI MANA SAJA di
                // label (mis. "Kepala Keluarga Perempuan"), bukan sama-persis —
                // kalau tetap tak ketemu, jatuh ke urutan [biru, merah muda].
                function warnaLP(l, i) {
                    if (/perempuan|wanita/i.test(l)) return W.perempuan;
                    if (/laki|pria/i.test(l)) return W.laki;
                    return [W.laki, W.perempuan][i] ?? K[i % K.length];
                }
                // opts.pusat = false → tanpa angka besar di tengah donat (dipakai
                // KPI Kota Dashboard yang totalnya sudah dicetak besar DI ATAS chart,
                // supaya tidak dobel & tidak menimpa donatnya).
                function donutLP(id, seri, subtext, opts = {}) {
                    const c = document.getElementById(id);
                    if (!c || !seri) return;
                    hancurkanJikaAda(id);
                    const total = seri.values.reduce((a, b) => a + b, 0);
                    new Chart(c, {
                        type: 'doughnut',
                        data: { labels: seri.labels, datasets: [{ data: seri.values, backgroundColor: seri.labels.map(warnaLP), borderWidth: 0, hoverOffset: 4 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '68%', layout: { padding: 16 },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                centerText: { display: opts.pusat !== false, text: fmt(total), subtext },
                                datalabels: { display: true, anchor: 'end', align: 'end', offset: 6, color: '#374151', font: { size: 10, weight: '600' }, formatter: (v) => fmt(v) },
                            },
                        },
                    });
                }

                // ── Bar horizontal (Pendidikan, Pekerjaan, Agama, Status Kawin, dst) ──
                function barH(id, seri, opts = {}) {
                    const c = document.getElementById(id);
                    if (!c || !seri) return;
                    hancurkanJikaAda(id);
                    const total = seri.values.reduce((a, b) => a + b, 0);
                    new Chart(c, {
                        type: 'bar',
                        data: { labels: seri.labels, datasets: [{ data: seri.values, backgroundColor: opts.warna ?? seri.labels.map((_, i) => K[i % K.length]), borderRadius: opts.radius ?? 4, borderSkipped: false }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                datalabels: { display: true, anchor: 'end', align: 'end', clamp: true, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                            },
                            scales: {
                                x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                                y: { grid: { display: false }, ticks: { font: { size: opts.ySize ?? 9 } } },
                            },
                        },
                    });
                }

                // ── Stacked bar per kelurahan (Memiliki vs Belum) ──
                function stackedKelurahan(id, data) {
                    const c = document.getElementById(id);
                    if (!c || !data) return;
                    hancurkanJikaAda(id);
                    const labels = Object.keys(data);
                    const memiliki = labels.map((k) => data[k].memiliki ?? 0);
                    const belum = labels.map((k) => data[k].belum ?? 0);
                    new Chart(c, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                { label: 'Memiliki', data: memiliki, backgroundColor: W.positif, borderRadius: 3, borderSkipped: false },
                                { label: 'Belum', data: belum, backgroundColor: '#FBCFE8', borderRadius: 3, borderSkipped: false },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } } },
                                tooltip: { callbacks: { label: (ct) => { const t = memiliki[ct.dataIndex] + belum[ct.dataIndex]; return `${ct.dataset.label}: ${fmt(ct.raw)} (${window.formatPersen(t > 0 ? ct.raw / t * 100 : 0)})`; } } },
                            },
                            scales: {
                                x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                                y: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 } } },
                            },
                        },
                    });
                }

                // ── DEMOGRAFI ──
                donutLP('chart-gender', d.gender, 'jiwa');
                donutLP('chart-anak', d.anak, 'anak');
                donutLP('chart-lansia', d.lansia, 'lansia');
                barH('chart-marital', d.marital, { radius: 6, ySize: 10 });

                if (d.disab && document.getElementById('chart-disab')) {
                    hancurkanJikaAda('chart-disab');
                    const disabTotal = d.disab.values.reduce((a, b) => a + b, 0);
                    new Chart(document.getElementById('chart-disab'), {
                        type: 'doughnut',
                        data: { labels: d.disab.labels, datasets: [{ data: d.disab.values, backgroundColor: d.disab.labels.map((_, i) => K[i % K.length]), borderWidth: 0, hoverOffset: 4 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '60%', layout: { padding: 16 },
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 10, font: { size: 9 } } },
                                tooltip: { callbacks: { label: window.tooltipPersenLabel(disabTotal) } },
                                centerText: { display: true, text: fmt(disabTotal), subtext: 'jiwa' },
                                datalabels: { display: true, anchor: 'end', align: 'end', offset: 4, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                            },
                        },
                    });
                }

                if (d.piramida && document.getElementById('chart-piramida')) {
                    hancurkanJikaAda('chart-piramida');
                    new Chart(document.getElementById('chart-piramida'), {
                        type: 'bar',
                        data: {
                            labels: d.piramida.labels,
                            datasets: [
                                // Utk bar horizontal, anchor 'start' = tepi KIRI elemen bar,
                                // 'end' = tepi KANAN. Bar laki (nilai negatif) membentang
                                // [-N, 0] → tepi kiri = ujungnya (-N) → anchor:'start' +
                                // align:'left' (mutlak) = angka DI LUAR ujung kiri. Bar
                                // perempuan [0, +N] → tepi kanan = ujungnya → anchor:'end'
                                // + align:'right' = angka di luar ujung kanan.
                                { label: 'Laki-laki', data: d.piramida.laki.map((v) => -v), backgroundColor: W.laki, borderRadius: 3, borderSkipped: false,
                                  datalabels: { anchor: 'start', align: 'left' } },
                                { label: 'Perempuan', data: d.piramida.perempuan, backgroundColor: W.perempuan, borderRadius: 3, borderSkipped: false,
                                  datalabels: { anchor: 'end', align: 'right' } },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, indexAxis: 'y', layout: { padding: { left: 52, right: 52 } },
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
                                tooltip: { callbacks: { label: (c) => { const nilai = Math.abs(c.raw); const total = d.total_penduduk; const persen = total > 0 ? window.formatPersen(nilai / total * 100) : ''; return `${c.dataset.label}: ${fmt(nilai)} jiwa${persen ? ` (${persen})` : ''}`; } } },
                                datalabels: { display: true, offset: 6, clamp: false, clip: false, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(Math.abs(v)) },
                            },
                            scales: {
                                x: { stacked: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 9 }, callback: (v) => fmt(Math.abs(v)) } },
                                y: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } },
                            },
                        },
                    });
                }

                // ── SOSIAL ──
                barH('chart-edu', d.edu);
                barH('chart-job', d.job);
                barH('chart-usia-sekolah', d.usia_sekolah);
                barH('chart-agama', d.agama);
                barH('chart-ktp-status', d.ktp_status);
                barH('chart-kk-status', d.kk_status);
                barH('chart-goldar', d.goldar);
                barH('chart-shbkel', d.shbkel);
                donutCenter('chart-kia', d.kia, 'anak', [W.positif, '#FBCFE8']);
                donutCenter('chart-akta-lahir', d.akta_lahir, 'jiwa', [W.positif, '#FBCFE8']);
                donutLP('chart-kk-jk', d.kk_jk, 'KK');
                stackedKelurahan('chart-akta-lahir-kelurahan', d.akta_lahir_kelurahan);
                stackedKelurahan('chart-kia-kelurahan', d.kia_kelurahan);
                stackedKelurahan('chart-ktp-kelurahan', d.ktp_kelurahan);

                // ── DASHBOARD PUBLIK: KPI Kota (donut L/P) + Tren se-Kota ──
                donutLP('chart-kpi-ktp', d.kpi_ktp, 'wajib KTP', { pusat: false });
                donutLP('chart-kpi-penduduk', d.kpi_penduduk, 'jiwa', { pusat: false });
                donutLP('chart-kpi-kk', d.kpi_kk, 'KK', { pusat: false });
                if (d.tren_kota && d.tren_kota.labels && d.tren_kota.labels.length) {
                    const tipeK = d.tren_kota.labels.length > 1 ? 'line' : 'bar';
                    const trenK = (id, arr, warna, satuan) => {
                        const c = document.getElementById(id);
                        if (!c) return;
                        hancurkanJikaAda(id);
                        new Chart(c, {
                            type: tipeK,
                            data: { labels: d.tren_kota.labels, datasets: [{ label: satuan, data: arr, borderColor: warna, backgroundColor: tipeK === 'line' ? 'rgba(13,148,136,.10)' : warna, tension: .35, fill: tipeK === 'line', borderRadius: 4, borderSkipped: false, pointRadius: 5, pointHoverRadius: 7 }] },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { callbacks: { label: (c) => `${fmt(c.raw)} ${satuan}` } },
                                    datalabels: { display: true, align: tipeK === 'line' ? 'top' : 'end', anchor: tipeK === 'bar' ? 'end' : undefined, color: '#374151', font: { size: 8, weight: '600' }, formatter: (v) => fmt(v) },
                                },
                                scales: { x: { grid: { display: false } }, y: { grid: { color: '#f0f0f0' }, beginAtZero: false, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } } },
                            },
                        });
                    };
                    trenK('chart-tren-penduduk', d.tren_kota.penduduk, W.total, 'jiwa');
                    trenK('chart-tren-kepadatan', d.tren_kota.kepadatan, W.total, 'jiwa/km²');
                }

                // ── MOBILITAS ──
                barH('chart-datang', d.datang, { warna: W.positif });
                barH('chart-pindah', d.pindah, { warna: W.negatif });
                if (d.trend && document.getElementById('chart-trend')) {
                    hancurkanJikaAda('chart-trend');
                    const tipe = d.trend.labels.length > 1 ? 'line' : 'bar';
                    new Chart(document.getElementById('chart-trend'), {
                        type: tipe,
                        data: {
                            labels: d.trend.labels,
                            datasets: tipe === 'line'
                                ? [
                                    { label: 'Pendatang', data: d.trend.datang, borderColor: W.positif, backgroundColor: 'rgba(16,185,129,.08)', tension: .35, fill: true, pointRadius: 5, pointHoverRadius: 7 },
                                    { label: 'Pindah Keluar', data: d.trend.pindah, borderColor: W.negatif, backgroundColor: 'rgba(239,68,68,.06)', tension: .35, fill: true, pointRadius: 5, pointHoverRadius: 7 },
                                ]
                                : [
                                    { label: 'Pendatang', data: d.trend.datang, backgroundColor: W.positif, borderRadius: 4, borderSkipped: false },
                                    { label: 'Pindah Keluar', data: d.trend.pindah, backgroundColor: W.negatif, borderRadius: 4, borderSkipped: false },
                                ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { display: tipe === 'bar', position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
                                tooltip: { mode: 'index', intersect: false, callbacks: { label: (c) => `${c.dataset.label}: ${fmt(c.raw)} jiwa` } },
                                datalabels: { display: true, align: tipe === 'line' ? 'top' : 'end', anchor: tipe === 'bar' ? 'end' : undefined, color: '#374151', font: { size: 8, weight: '600' }, formatter: (v) => fmt(v) },
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { grid: { color: '#f0f0f0' }, beginAtZero: true, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                            },
                        },
                    });
                }
    };

    // ── Perbandingan Antar Periode (bagian grid, bisa dipindah) ──
            function perbandinganBox(cfg = {}) {
                const fmt = (n) => new Intl.NumberFormat('id-ID').format(n ?? 0);

                return {
                    loading: true,
                    ada: true,
                    // `fmt` WAJIB jadi properti data (bukan cuma const di closure) —
                    // template pakai `x-text="fmt(...)"` langsung (di kartu total &
                    // tabel rincian), dan Alpine cuma bisa mengakses PROPERTI objek
                    // data ini saat mengevaluasi expression di HTML, bukan variabel
                    // closure JS biasa. Tanpa ini seluruh `fmt(...)` di template
                    // gagal senyap ("fmt is not defined") — itulah sebab kartu total,
                    // tabel rincian, dan filter "Cari kategori" semua tampak tidak
                    // berfungsi (nilainya gagal dicetak, bukan filternya yang rusak).
                    fmt,
                    // Default "Jenis Kelamin" — jumlahnya (L+P) = jumlah penduduk,
                    // indikator yang paling relevan ditampilkan pertama kali.
                    kategori: 'jenis_kelamin',
                    // Default: data yang PALING BARU DIUPLOAD (waktuId2) vs
                    // sebelumnya (waktuId1) — lihat DimWaktu::duaTerbaruDiupload()
                    // di DashboardPublikController. BUKAN sekadar tahun/semester
                    // terbesar, karena urutan upload bisa beda dari urutan periode.
                    waktuId1: cfg.w1 ?? null,
                    waktuId2: cfg.w2 ?? null,
                    periode1: '',
                    periode2: '',
                    pilih: '',
                    labelsAsli: [],
                    nilai1Asli: [],
                    nilai2Asli: [],
                    labels: [],
                    nilai1: [],
                    nilai2: [],

                    async init() {
                        await this.muat();
                    },

                    // Total SELALU dari seluruh label (labelsAsli/nilai*Asli, bukan
                    // labels/nilai* yang sudah tersaring), jadi ringkasan total tidak
                    // ikut berubah saat memfilter satu kategori di daftar bawah.
                    totalPeriode1() {
                        return this.nilai1Asli.reduce((a, b) => a + b, 0);
                    },
                    totalPeriode2() {
                        return this.nilai2Asli.reduce((a, b) => a + b, 0);
                    },
                    selisihTotal() {
                        return this.totalPeriode2() - this.totalPeriode1();
                    },
                    persenTotal() {
                        const t1 = this.totalPeriode1();
                        if (t1 <= 0) return '';
                        const persen = (this.selisihTotal() / t1) * 100;
                        return (persen > 0 ? '+' : '') + persen.toFixed(1) + '% dari periode sebelumnya';
                    },

                    async muat() {
                        this.loading = true;
                        this.pilih = '';
                        try {
                            const params = new URLSearchParams({
                                jenis_indikator: this.kategori,
                                waktu_id_1: this.waktuId1,
                                waktu_id_2: this.waktuId2,
                            });
                            const res = await fetch('{{ route('api.dashboard-publik.kategori') }}?' + params.toString());
                            const json = await res.json();
                            this.ada = true;
                            this.labelsAsli = json.labels ?? [];
                            this.nilai1Asli = json.nilai1 ?? [];
                            this.nilai2Asli = json.nilai2 ?? [];
                            this.periode1 = json.periode1 ?? '';
                            this.periode2 = json.periode2 ?? '';
                        } catch (e) {
                            console.error('Gagal memuat data perbandingan', e);
                            this.ada = false;
                            this.labelsAsli = [];
                            this.nilai1Asli = [];
                            this.nilai2Asli = [];
                        }
                        this.loading = false;
                        await this.$nextTick();
                        this.render();
                    },

                    // `pilih` (dipilih lewat dropdown "Cari kategori") menyaring
                    // labels/nilai1/nilai2 SEBELUM dipakai chart maupun daftar
                    // rincian di bawahnya — keduanya pakai array yang sama ini,
                    // jadi memilih kategori benar-benar menyaring chart-nya juga,
                    // bukan cuma daftar angka di bawah.
                    render() {
                        if (this.pilih === '') {
                            // .slice() — array BARU, bukan referensi yang sama dengan
                            // labelsAsli/nilai*Asli. Chart.js akan diberi array ini
                            // langsung (chart.data.labels = this.labels); kalau ini
                            // referensi YANG SAMA dengan array reaktif Alpine, Chart.js
                            // menambahkan properti metadata internalnya ke array yang
                            // SAMA yang juga dilacak Alpine — sumber lain risiko
                            // "Maximum call stack" selain masalah chart-di-reaktif di
                            // bawah. Salinan independen menghindarinya sama sekali.
                            this.labels = this.labelsAsli.slice();
                            this.nilai1 = this.nilai1Asli.slice();
                            this.nilai2 = this.nilai2Asli.slice();
                        } else {
                            const idx = this.labelsAsli.indexOf(this.pilih);
                            this.labels = idx === -1 ? [] : [this.pilih];
                            this.nilai1 = idx === -1 ? [] : [this.nilai1Asli[idx]];
                            this.nilai2 = idx === -1 ? [] : [this.nilai2Asli[idx]];
                        }

                        const canvas = document.getElementById('chart-perbandingan');
                        if (!canvas) return;

                        // Hancurkan & buat ulang chart dari nol tiap render() —
                        // BUKAN cuma ganti chart.data lalu update(). Saat ganti
                        // indikator, jumlah kategori bisa melompat drastis (mis.
                        // Jenis Kelamin 2 kategori → Pekerjaan 11 kategori), dan
                        // Chart.js pernah terbukti error "Maximum call stack size
                        // exceeded" di resolver opsi internalnya kalau instance lama
                        // "dipaksa" menyesuaikan bentuk data yang jauh berbeda lewat
                        // update(). destroy()+new Chart() selalu mulai dari state
                        // bersih, menghindari kelas bug ini sepenuhnya.
                        //
                        // PENTING (2026-09-11): pakai REGISTRI ASLI Chart.js
                        // (`Chart.getChart(canvas)`), BUKAN properti kustom
                        // (`canvas._chartInstance`) seperti sebelumnya. Kalau
                        // `new Chart(...)` gagal di tengah jalan (exception apa
                        // pun), Chart.js SUDAH mendaftarkan kanvas itu ke
                        // registrinya sendiri SEBELUM konstruktor selesai —
                        // sedangkan properti kustom kita TIDAK PERNAH ke-set
                        // (baris assignment tidak tercapai). Akibatnya kanvas
                        // "nyangkut": Chart.js menolak semua percobaan
                        // berikutnya ("Canvas is already in use ... must be
                        // destroyed") padahal `canvas._chartInstance` kita
                        // masih null — inilah sebab tersangka bug "ganti
                        // indikator, chart-nya diam tidak berubah" (macet di
                        // gambar TERAKHIR yang berhasil, lalu semua switch
                        // berikutnya gagal senyap tanpa pernah pulih sampai
                        // halaman dimuat ulang). `Chart.getChart()` SELALU
                        // akurat karena itu sumber kebenaran Chart.js sendiri
                        // — sama pola dengan `hancurkanJikaAda()` di atas.
                        const lama = window.Chart.getChart(canvas);
                        if (lama) lama.destroy();

                        if (!this.labels.length) return;

                        try {
                            new Chart(canvas, {
                                type: 'bar',
                                data: {
                                    labels: this.labels.slice(),
                                    datasets: [{
                                            label: this.periode1,
                                            data: this.nilai1.slice(),
                                            backgroundColor: '#93c5fd',
                                            borderRadius: 4,
                                            borderSkipped: false
                                        },
                                        {
                                            label: this.periode2,
                                            data: this.nilai2.slice(),
                                            backgroundColor: '#1d4ed8',
                                            borderRadius: 4,
                                            borderSkipped: false
                                        },
                                    ],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    indexAxis: 'y',
                                    layout: {
                                        padding: {
                                            right: 40
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: {
                                                boxWidth: 10,
                                                font: {
                                                    size: 10
                                                }
                                            }
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: (c) => `${c.dataset.label}: ${fmt(c.raw)} jiwa`
                                            }
                                        },
                                        datalabels: {
                                            display: true,
                                            anchor: 'end',
                                            align: 'end',
                                            clamp: true,
                                            color: '#374151',
                                            font: {
                                                size: 9,
                                                weight: '600'
                                            },
                                            formatter: (v) => fmt(v),
                                        },
                                    },
                                    scales: {
                                        x: {
                                            grid: {
                                                display: false
                                            },
                                            ticks: {
                                                font: {
                                                    size: 10
                                                },
                                                callback: (v) => v >= 1000 ? `${v / 1000}k` : v
                                            }
                                        },
                                        y: {
                                            grid: {
                                                display: false
                                            },
                                            ticks: {
                                                font: {
                                                    size: 10
                                                }
                                            }
                                        },
                                    },
                                },
                            });
                        } catch (e) {
                            // Jangan biarkan exception di sini diam-diam
                            // "membekukan" chart selamanya — tercatat di
                            // console supaya kalau muncul lagi gejalanya
                            // langsung terlihat, bukan cuma "tidak berubah".
                            console.error('Gagal menggambar chart perbandingan', e);
                        }
                    },
                };
            }
</script>
