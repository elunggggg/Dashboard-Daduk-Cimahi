<x-layouts.public title="Dashboard Publik">

    {{-- x-init="init()" SENGAJA TIDAK ditambahkan — `init` adalah nama method
         "ajaib" di Alpine.js, otomatis dipanggil sekali saat komponen siap TANPA
         perlu x-init. Menambahkannya di sini membuat init() terpanggil DUA KALI
         (race condition), menyebabkan chart dobel di canvas yang sama ("Canvas is
         already in use") dan korupsi state Chart.js yang bikin update berikutnya
         (mis. ganti filter) gagal senyap. Ini pernah jadi bug nyata di sesi
         2026-08-25 — JANGAN tambahkan x-init="init()" lagi di komponen manapun yang
         method-nya sudah bernama `init`. ── --}}
    <div x-data="dashboardApp()">
        {{-- ── BAGIAN 1: Header masthead ──
             Latar belakang dapat diganti Petugas lewat /petugas/pengaturan (Tampilan
             Dashboard Publik). Kotak header punya UKURAN TETAP `h-48 sm:h-64` DI
             KEDUA KEADAAN (ada gambar MAUPUN tanpa gambar/judul teks polos) — SENGAJA
             SAMA PERSIS di kedua cabang @if/@else di bawah, supaya menambah/menghapus
             gambar TIDAK membuat kotak header melompat berubah tinggi. Gambar apa pun
             yang diunggah (potret/lanskap/persegi) MENYESUAIKAN ke ukuran kotak ini
             lewat `object-contain` (diskalakan utuh tanpa terpotong, sisa ruang kosong
             kalau rasionya beda, BUKAN kotaknya yang berubah ukuran). Judul teks
             "Dashboard Statistik Kependudukan..." SENGAJA DISEMBUNYIKAN total saat
             ada gambar — gambarnya sendiri yang jadi identitas header (lihat contoh
             di docs/image.png). ── --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            @if ($latarBelakang)
                <div
                    class="rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-sm h-48 sm:h-64 flex items-center justify-center">
                    <img src="{{ $latarBelakang }}" alt="Dashboard Statistik Kependudukan {{ $namaInstansi }}"
                        class="w-full h-full object-contain p-3 sm:p-4">
                </div>
            @else
                <div
                    class="rounded-2xl bg-white border border-gray-200 shadow-sm h-48 sm:h-64 flex items-center justify-center px-4">
                    <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight text-gray-900 text-center">
                        Dashboard Statistik Kependudukan<br class="sm:hidden">
                        <span class="hidden sm:inline"> </span>{{ $namaInstansi }}
                    </h1>
                </div>
            @endif
        </div>

        {{-- ── Latar belakang body: gambar terpisah dari header, diganti Petugas
             lewat /petugas/pengaturan. Kalau belum ada gambar, jatuh ke bg-gray-50
             bawaan layout publik. ── --}}
        <div
            @if ($latarBelakangBody) style="background-image: linear-gradient(to bottom, rgba(249,250,251,.35), rgba(249,250,251,.55)), url('{{ $latarBelakangBody }}'); background-size: cover; background-position: top; background-attachment: fixed;" @endif>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

                {{-- ── Ekspor Data — bisa dipakai Petugas maupun Publik tanpa login.
                     Lihat EksporController::unduh() & DashboardPublikController. ── --}}
                <div class="card p-4">
                    @if (session('success'))
                        <div class="alert-success mb-3 flex items-center gap-2 text-xs">
                            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert-error mb-3 flex items-center gap-2 text-xs">
                            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                        </div>
                    @endif

                    <h2 class="text-sm font-bold text-gray-900 flex items-center gap-1.5 mb-1">
                        <i class="bi bi-file-earmark-arrow-down text-brand-700"></i> Ekspor Data
                    </h2>
                    <p class="text-xs text-gray-400 mb-3">Pilih penyaring lalu unduh data mentah dalam Excel atau PDF.</p>

                    <form method="POST" action="{{ route('ekspor.unduh') }}">
                        @csrf
                        <div class="flex flex-wrap items-end gap-3">
                            @if ($waktuList->count() > 1)
                                <div class="flex-1 min-w-[140px]">
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Periode</label>
                                    <select name="waktu_id" class="form-select text-xs py-2 w-full">
                                        <option value="">Semua periode</option>
                                        @foreach ($waktuList as $w)
                                            <option value="{{ $w->id }}" @selected(old('waktu_id') == $w->id)>{{ $w->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Kecamatan</label>
                                <select name="kecamatan" class="form-select text-xs py-2 w-full">
                                    <option value="">Semua kecamatan</option>
                                    @foreach ($kecamatanList as $kec)
                                        <option value="{{ $kec }}" @selected(old('kecamatan') === $kec)>{{ $kec }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex-1 min-w-[180px]">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Indikator</label>
                                <select name="jenis_indikator" class="form-select text-xs py-2 w-full">
                                    <option value="">Semua indikator</option>
                                    @foreach ($eksporIndikatorList as $kode => $nama)
                                        <option value="{{ $kode }}" @selected(old('jenis_indikator') === $kode)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Format berkas</label>
                                <select name="format" class="form-select text-xs py-2 w-full">
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="pdf">PDF (maks. 3.000 baris)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn-primary text-xs py-2 px-4 flex-shrink-0">
                                <i class="bi bi-download"></i> Unduh
                            </button>
                        </div>

                        @error('format')
                            <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                </div>

                {{-- Empty state (dari api.dashboard) — KPI & Tren se-Kota kini
                     dirender server di zona grid di bawah (dashboard/_konten_kota),
                     bisa dipindah/disembunyikan lewat menu "Bagian Dashboard". --}}
                <div x-show="!loading && data && !data.ada_data" x-cloak class="card p-16 text-center text-gray-400">
                    <i class="bi bi-database-x text-3xl block mb-2"></i>
                    Belum ada data. Silakan import data terlebih dahulu.
                </div>


                {{-- ── Bagian yang dipindah Petugas ke halaman "dashboard" ──
                     (menu "Bagian Dashboard"). Se-Kota, periode terbaru; tanpa
                     filter di sini. Kalau belum ada yang dipindah, tak tampil. --}}
                @if (str_contains($kontenGrid, 'seksi-item'))
                    <div x-data="dashboardGridApp({ konten_html: @js($kontenGrid), charts: @js($gridCharts), rute: '' })">
                        <div x-html="kontenHtml"></div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <x-slot:scripts>
        @include('dashboard._skrip')
        <script>
            function dashboardApp() {
                const fmt = (n) => new Intl.NumberFormat('id-ID').format(n ?? 0);

                // Total sudah dicetak besar DI LUAR chart (di atas canvas, lihat
                // Blade) — donat di sini murni ilustrasi proporsi L/P, jadi TIDAK
                // ada lagi centerText/datalabels di dalamnya (dulu ada, sengaja
                // dihapus supaya tidak ada angka lagi "di dalam" chart).
                return {
                    loading: true,
                    data: null,
                    fmt,

                    async init() {
                        this.loading = true;
                        try {
                            const res = await fetch('{{ route('api.dashboard') }}');
                            this.data = await res.json();
                        } catch (e) {
                            console.error('Gagal memuat data dashboard', e);
                            this.data = {
                                ada_data: false
                            };
                        }
                        this.loading = false;
                        // KPI & Tren se-Kota kini dirender server di zona grid
                        // (dashboard/_konten_kota) + digambar oleh gambarSemuaChart()
                        // di dashboard/_skrip. dashboardApp() tinggal dipakai untuk
                        // status "belum ada data" saja.
                    },

                    donutGender(id, obj) {
                        const canvas = document.getElementById(id);
                        if (!canvas) return;
                        // Instance disimpan sebagai properti PADA ELEMEN CANVAS
                        // (`canvas._chartInstance`) — bukan closure/properti reaktif
                        // Alpine, lihat catatan panjang di perbandinganBox() bagian
                        // render(). Jaga-jaga kalau donutGender terpanggil lagi untuk
                        // canvas yang sama — hancurkan instance lama dulu, Chart.js
                        // menolak 2 instance di canvas yang sama.
                        if (canvas._chartInstance) {
                            canvas._chartInstance.destroy();
                        }
                        const WARNA = window.DadukColors;
                        canvas._chartInstance = new Chart(canvas, {
                            type: 'doughnut',
                            data: {
                                labels: ['Laki-laki', 'Perempuan'],
                                datasets: [{
                                    data: [obj.laki, obj.perempuan],
                                    backgroundColor: [WARNA.laki, WARNA.perempuan],
                                    borderWidth: 0,
                                    hoverOffset: 4
                                }],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '68%',
                                layout: {
                                    padding: 12
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    // Tooltip standar: label + angka + persentase (bukan cuma
                                    // angka mentah), sama pola dengan chart lain di aplikasi ini
                                    // (lihat window.tooltipPersenLabel di resources/js/app.js).
                                    tooltip: {
                                        callbacks: {
                                            label: window.tooltipPersenLabel(obj.laki + obj.perempuan)
                                        }
                                    },
                                    // Angka DI LUAR lingkaran (anchor+align 'end'),
                                    // bukan menimpa warna slice di dalamnya — total
                                    // sudah dicetak besar di ATAS chart (lihat Blade).
                                    datalabels: {
                                        display: true,
                                        anchor: 'end',
                                        align: 'end',
                                        offset: 4,
                                        color: '#374151',
                                        font: {
                                            size: 9,
                                            weight: '600'
                                        },
                                        formatter: (v) => fmt(v),
                                    },
                                },
                            },
                        });
                    },

                    // Line kalau >1 periode, bar kalau cuma 1 — pola sama
                    // dengan Tren Mobilitas.
                    trenChart(id, tren, kunci, satuan) {
                        const canvas = document.getElementById(id);
                        if (!canvas || !tren?.labels?.length) return;
                        if (canvas._chartInstance) {
                            canvas._chartInstance.destroy();
                        }
                        const W = window.DadukColors;
                        const labels = tren.labels;
                        const values = tren[kunci];
                        const tipe = labels.length > 1 ? 'line' : 'bar';
                        const dataset = tipe === 'line'
                            ? { label: satuan, data: values, borderColor: W.total, backgroundColor: 'rgba(13,148,136,.08)', tension: .35, fill: true, pointRadius: 5, pointHoverRadius: 7 }
                            : { label: satuan, data: values, backgroundColor: W.total, borderRadius: 4, borderSkipped: false };
                        canvas._chartInstance = new Chart(canvas, {
                            type: tipe,
                            data: { labels, datasets: [dataset] },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: { callbacks: { label: (c) => `${fmt(c.raw)} ${satuan}` } },
                                    datalabels: {
                                        display: true,
                                        align: tipe === 'line' ? 'top' : 'end',
                                        anchor: tipe === 'bar' ? 'end' : undefined,
                                        color: '#374151',
                                        font: { size: 9, weight: '600' },
                                        formatter: (v) => fmt(v),
                                    },
                                },
                                scales: {
                                    x: { grid: { display: false } },
                                    y: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                                },
                            },
                        });
                    },
                };
            }

        </script>
    </x-slot:scripts>

</x-layouts.public>
