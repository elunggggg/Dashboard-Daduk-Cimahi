<script>
    // ── x-data bersama Halaman Demografi & Sosial ──
    // Kedua halaman kini memakai satu fungsi + satu gambarSemuaChart(): payload
    // `charts` berisi gabungan metrik kedua halaman, dan tiap kanvas hanya
    // digambar kalau elemennya ADA di DOM (bagian yang disembunyikan / dipindah
    // ke halaman lain tidak punya kanvas → dilewati). Dipakai untuk kunjungan
    // pertama (init) maupun tiap fetch filter (muat()).
    function dashboardApp(seed) {
        return {
            loading: false,
            kontenHtml: seed.konten_html,
            _rute: seed.rute,

            init() {
                this.$nextTick(() => this.gambarSemuaChart(seed.charts));
            },

            async muat(filter) {
                this.loading = true;

                const params = new URLSearchParams();
                if (filter.kecamatan) params.set('kecamatan', filter.kecamatan);
                if (filter.wilayah_id) params.set('wilayah_id', filter.wilayah_id);
                if (filter.waktu_id) params.set('waktu_id', filter.waktu_id);

                const url = this._rute + (params.toString() ? '?' + params.toString() : '');
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
                    console.error('Gagal memuat data dashboard', e);
                    this.loading = false;
                }
            },

            gambarSemuaChart(d) {
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
                function donutLP(id, seri, subtext) {
                    const c = document.getElementById(id);
                    if (!c || !seri) return;
                    hancurkanJikaAda(id);
                    const total = seri.values.reduce((a, b) => a + b, 0);
                    new Chart(c, {
                        type: 'doughnut',
                        data: { labels: seri.labels, datasets: [{ data: seri.values, backgroundColor: seri.labels.map((l) => l === 'Laki-laki' ? W.laki : (l === 'Perempuan' ? W.perempuan : K[0])), borderWidth: 0, hoverOffset: 4 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, cutout: '68%', layout: { padding: 16 },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                centerText: { display: true, text: fmt(total), subtext },
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
                                { label: 'Laki-laki', data: d.piramida.laki.map((v) => -v), backgroundColor: W.laki, borderRadius: 3, borderSkipped: false },
                                { label: 'Perempuan', data: d.piramida.perempuan, backgroundColor: W.perempuan, borderRadius: 3, borderSkipped: false },
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false, indexAxis: 'y', layout: { padding: { left: 30, right: 30 } },
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
                                tooltip: { callbacks: { label: (c) => { const nilai = Math.abs(c.raw); const total = d.total_penduduk; const persen = total > 0 ? window.formatPersen(nilai / total * 100) : ''; return `${c.dataset.label}: ${fmt(nilai)} jiwa${persen ? ` (${persen})` : ''}`; } } },
                                datalabels: { display: true, anchor: 'end', align: 'end', clamp: true, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(Math.abs(v)) },
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
            },
        };
    }
</script>
