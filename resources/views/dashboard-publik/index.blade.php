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
                    <img src="{{ $latarBelakang }}" alt="Dashboard Statistik Kependudukan Disdukcapil Kota Cimahi"
                        class="w-full h-full object-contain p-3 sm:p-4">
                </div>
            @else
                <div
                    class="rounded-2xl bg-white border border-gray-200 shadow-sm h-48 sm:h-64 flex items-center justify-center px-4">
                    <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight text-gray-900 text-center">
                        Dashboard Statistik Kependudukan<br class="sm:hidden">
                        <span class="hidden sm:inline"> </span>Disdukcapil Kota Cimahi
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

                {{-- ── Ekspor Laporan — ringkas, satu baris, di atas ──
                     Bisa dipakai Petugas maupun Publik tanpa login. Riwayat
                     disembunyikan di balik tombol supaya baris ini tetap ringkas —
                     lihat EksporController::unduh() & DashboardPublikController. ── --}}
                <div class="card p-3" x-data="{ riwayatOpen: false }">
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

                    <form method="POST" action="{{ route('ekspor.unduh') }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <span class="text-xs font-semibold text-gray-700 flex items-center gap-1.5 flex-shrink-0">
                            <i class="bi bi-file-earmark-arrow-down text-brand-700"></i> Ekspor Data:
                        </span>

                        @if ($waktuList->count() > 1)
                            <select name="waktu_id" class="form-select text-xs py-1.5 w-auto">
                                <option value="">Semua Periode</option>
                                @foreach ($waktuList as $w)
                                    <option value="{{ $w->id }}" @selected(old('waktu_id') == $w->id)>{{ $w->label }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <select name="kecamatan" class="form-select text-xs py-1.5 w-auto">
                            <option value="">Semua kecamatan</option>
                            @foreach ($kecamatanList as $kec)
                                <option value="{{ $kec }}" @selected(old('kecamatan') === $kec)>{{ $kec }}
                                </option>
                            @endforeach
                        </select>

                        <select name="jenis_indikator" class="form-select text-xs py-1.5 w-auto">
                            <option value="">Semua indikator</option>
                            @foreach ($eksporIndikatorList as $ind)
                                <option value="{{ $ind }}" @selected(old('jenis_indikator') === $ind)>{{ $ind }}
                                </option>
                            @endforeach
                        </select>

                        <select name="format" class="form-select text-xs py-1.5 w-auto">
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="pdf">PDF (maks. 3.000 baris)</option>
                        </select>

                        <button type="submit" class="btn-primary text-xs py-1.5 px-3">
                            <i class="bi bi-download"></i> Unduh
                        </button>

                        <button type="button" @click="riwayatOpen = !riwayatOpen"
                            class="btn-secondary text-xs py-1.5 w-auto">
                            <i class="bi bi-clock-history"></i> Riwayat
                            <i class="bi text-[10px]" :class="riwayatOpen ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                        </button>
                    </form>

                    @error('format')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <div x-show="riwayatOpen" x-cloak x-transition class="mt-3 pt-3 border-t border-gray-100">
                        @forelse ($riwayatEkspor as $r)
                            <div
                                class="flex items-start gap-2.5 py-1.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                <i
                                    class="bi {{ $r->jenis === 'pdf' ? 'bi-file-earmark-pdf text-red-500' : 'bi-file-earmark-spreadsheet text-green-600' }} mt-0.5"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-800 leading-snug">{{ $r->judul }}</p>
                                    <p class="text-[10px] text-gray-400">
                                        {{ $r->user->name ?? 'Publik' }} ·
                                        {{ $r->created_at?->translatedFormat('d M Y H:i') }}
                                        @if (isset($r->parameter['jumlah_baris']))
                                            · {{ number_format($r->parameter['jumlah_baris'], 0, ',', '.') }} baris
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 text-center py-3">Belum ada ekspor.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Loading state — skeleton per card, bukan spinner tunggal
                     di tengah halaman (supaya bentuk KPI tetap terasa "ada"
                     saat data belum sampai). --}}
                <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-skeleton-card :rows="2" />
                    <x-skeleton-card :rows="2" />
                    <x-skeleton-card :rows="2" />
                </div>

                {{-- Empty state --}}
                <div x-show="!loading && data && !data.ada_data" x-cloak class="card p-16 text-center text-gray-400">
                    <i class="bi bi-database-x text-3xl block mb-2"></i>
                    Belum ada data. Silakan import data terlebih dahulu.
                </div>

                <div x-show="!loading && data && data.ada_data" x-cloak class="space-y-5">

                    <p class="text-xs text-gray-400 text-center">
                        <i class="bi bi-info-circle"></i>
                        Sumber: <span x-text="data?.sumber"></span>
                    </p>

                    {{-- ── KPI ──
                         Angka total SELALU dicetak besar DI LUAR chart (bukan cuma
                         di tengah donat) supaya jelas terbaca tanpa perlu menatap
                         chart-nya; donat di sini murni ilustrasi proporsi L/P, dan
                         rincian L/P dicetak sebagai daftar bergaris di bawahnya
                         (pola sama dengan modul lain), bukan cuma chip sebaris. ── --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="card p-4">
                            <div class="flex items-start justify-between mb-1">
                                <h2 class="text-sm font-bold text-gray-900">Jumlah Wajib KTP</h2>
                                <i class="bi bi-person-vcard text-amber-500"></i>
                            </div>
                            <p class="text-[11px] text-amber-600 mb-1" x-show="data?.kpi?.wajib_ktp?.estimasi">
                                <i class="bi bi-exclamation-triangle"></i> rincian L/P ditaksir
                            </p>
                            <p class="text-3xl font-extrabold text-gray-900 tracking-tight text-center my-2"
                                x-text="fmt(data?.kpi?.wajib_ktp?.total)"></p>
                            <p class="text-[11px] text-gray-400 text-center mb-3">jiwa wajib KTP</p>
                            <div class="h-36"><canvas id="chart-kpi-ktp"></canvas></div>
                            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 text-gray-600"><span
                                            class="h-2 w-2 rounded-full"
                                            style="background:{{ config('dashboard-colors.laki') }}"></span>Laki-laki</span>
                                    <strong class="text-gray-900" x-text="fmt(data?.kpi?.wajib_ktp?.laki)"></strong>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 text-gray-600"><span
                                            class="h-2 w-2 rounded-full"
                                            style="background:{{ config('dashboard-colors.perempuan') }}"></span>Perempuan</span>
                                    <strong class="text-gray-900"
                                        x-text="fmt(data?.kpi?.wajib_ktp?.perempuan)"></strong>
                                </div>
                            </div>
                        </div>
                        <div class="card p-4">
                            <div class="flex items-start justify-between mb-1">
                                <h2 class="text-sm font-bold text-gray-900">Jumlah Penduduk</h2>
                                <i class="bi bi-people-fill text-blue-500"></i>
                            </div>
                            <p class="text-[11px] text-transparent mb-1 select-none">.</p>
                            <p class="text-3xl font-extrabold text-gray-900 tracking-tight text-center my-2"
                                x-text="fmt(data?.kpi?.penduduk?.total)"></p>
                            <p class="text-[11px] text-gray-400 text-center mb-3">jiwa</p>
                            <div class="h-36"><canvas id="chart-kpi-penduduk"></canvas></div>
                            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 text-gray-600"><span
                                            class="h-2 w-2 rounded-full"
                                            style="background:{{ config('dashboard-colors.laki') }}"></span>Laki-laki</span>
                                    <strong class="text-gray-900" x-text="fmt(data?.kpi?.penduduk?.laki)"></strong>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 text-gray-600"><span
                                            class="h-2 w-2 rounded-full"
                                            style="background:{{ config('dashboard-colors.perempuan') }}"></span>Perempuan</span>
                                    <strong class="text-gray-900"
                                        x-text="fmt(data?.kpi?.penduduk?.perempuan)"></strong>
                                </div>
                            </div>
                        </div>
                        <div class="card p-4">
                            <div class="flex items-start justify-between mb-1">
                                <h2 class="text-sm font-bold text-gray-900">Jumlah Kepala Keluarga</h2>
                                <i class="bi bi-house-door-fill text-teal-600"></i>
                            </div>
                            <p class="text-[11px] text-transparent mb-1 select-none">.</p>
                            <p class="text-3xl font-extrabold text-gray-900 tracking-tight text-center my-2"
                                x-text="fmt(data?.kpi?.kk?.total)"></p>
                            <p class="text-[11px] text-gray-400 text-center mb-3">Kepala Keluarga</p>
                            <div class="h-36"><canvas id="chart-kpi-kk"></canvas></div>
                            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 text-gray-600"><span
                                            class="h-2 w-2 rounded-full"
                                            style="background:{{ config('dashboard-colors.laki') }}"></span>Laki-laki</span>
                                    <strong class="text-gray-900" x-text="fmt(data?.kpi?.kk?.laki)"></strong>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="flex items-center gap-1.5 text-gray-600"><span
                                            class="h-2 w-2 rounded-full"
                                            style="background:{{ config('dashboard-colors.perempuan') }}"></span>Perempuan</span>
                                    <strong class="text-gray-900" x-text="fmt(data?.kpi?.kk?.perempuan)"></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── Tren Jumlah Penduduk & Kepadatan Penduduk ──
                         Se-Kota, lintas SELURUH periode (tidak ikut filter) —
                         line kalau >1 periode, bar kalau cuma 1 (pola sama
                         dengan Tren Mobilitas). Ditaruh di sini, DI ATAS
                         Perbandingan, sesuai permintaan. ── --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" x-show="data?.tren?.labels?.length">
                        <div class="section-card">
                            <h2 class="section-title">Tren Jumlah Penduduk</h2>
                            <p class="text-xs text-gray-400 -mt-2 mb-3">Se-Kota, antar periode</p>
                            <div class="h-[300px]"><canvas id="chart-tren-penduduk"></canvas></div>
                        </div>
                        <div class="section-card">
                            <h2 class="section-title">Tren Kepadatan Penduduk</h2>
                            <p class="text-xs text-gray-400 -mt-2 mb-3">Jiwa per km² — se-Kota, antar periode</p>
                            <div class="h-[300px]"><canvas id="chart-tren-kepadatan"></canvas></div>
                        </div>
                    </div>

                </div>

                {{-- ── Perbandingan ──
                 Box mandiri (Alpine sendiri, lepas dari dashboardApp): pilih
                 kategori + periode → satu bar chart rincian label kategori itu,
                 seluruh kota — lihat Api\KategoriPublikController::INDIKATOR_LIST.
                 x-init="init()" SENGAJA TIDAK dipakai (sama alasannya dengan
                 dashboardApp di atas — `init` nama method ajaib Alpine, otomatis
                 terpanggil sendiri, jangan dipanggil dobel). ── --}}
                <div class="card overflow-hidden" x-data="perbandinganBox()">
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-brand-50 to-white border-b border-gray-100">
                        <div class="flex items-start gap-3 mb-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-bar-chart-line-fill text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-sm font-bold text-gray-900">Perbandingan Antar Periode</h2>
                                <p class="text-xs text-gray-500">Bandingkan satu indikator antara dua periode data —
                                    pilih indikator dan periodenya di bawah.</p>
                            </div>
                        </div>

                        @if ($waktuList->count() >= 2)
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 mb-1 block">Indikator</label>
                                    <select class="form-select text-sm w-full" x-model="kategori" @change="muat()">
                                        @foreach ($indikatorList as $kunci => $label)
                                            <option value="{{ $kunci }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 mb-1 block">Periode
                                        Pembanding</label>
                                    <select class="form-select text-sm w-full" x-model="waktuId1" @change="muat()">
                                        @foreach ($waktuList as $w)
                                            <option value="{{ $w->id }}">{{ $w->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-gray-500 mb-1 block">Periode
                                        Terbaru</label>
                                    <select class="form-select text-sm w-full" x-model="waktuId2" @change="muat()">
                                        @foreach ($waktuList as $w)
                                            <option value="{{ $w->id }}">{{ $w->label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($waktuList->count() < 2)
                        <p class="py-12 text-center text-gray-400 text-sm">
                            <i class="bi bi-info-circle"></i> Perbandingan memerlukan minimal dua periode data.
                        </p>
                    @else
                        {{-- Skeleton loader (bukan spinner) — konsisten dengan pola KPI di atas. --}}
                        <div x-show="loading" class="p-4 sm:p-5 space-y-3">
                            <div class="skeleton h-40 w-full"></div>
                            <div class="skeleton h-3 w-2/3"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                        </div>
                        <div x-show="!loading && ada && !labels.length" x-cloak
                            class="py-16 text-center text-gray-400">
                            <i class="bi bi-database-x text-3xl block mb-2"></i>
                            Belum ada data untuk kombinasi ini.
                        </div>

                        <div x-show="!loading && labelsAsli.length" x-cloak>

                            {{-- ── Ringkasan total: total kategori ini di kedua periode + selisih ──
                                 Ini yang menjawab "chart-nya menunjukkan jumlah penduduk pada
                                 data upload terbaru dan sebelumnya" — total dijumlahkan dari
                                 SELURUH label kategori terpilih (jenis_kelamin default = L+P =
                                 jumlah penduduk), tidak ikut tersaring oleh filter "Cari kategori"
                                 di bawah supaya totalnya tetap utuh apa pun yang sedang difilter. ── --}}
                            <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                                <div class="p-4 text-center">
                                    <p class="text-[11px] text-gray-500 mb-1" x-text="periode1"></p>
                                    <p class="text-xl sm:text-2xl font-extrabold text-gray-700 tracking-tight"
                                        x-text="fmt(totalPeriode1())"></p>
                                </div>
                                <div class="p-4 text-center bg-brand-50/40">
                                    <p class="text-[11px] text-brand-700 font-semibold mb-1" x-text="periode2"></p>
                                    <p class="text-xl sm:text-2xl font-extrabold text-brand-800 tracking-tight"
                                        x-text="fmt(totalPeriode2())"></p>
                                </div>
                                <div class="p-4 text-center">
                                    <p class="text-[11px] text-gray-500 mb-1">Selisih</p>
                                    <p class="text-xl sm:text-2xl font-extrabold tracking-tight"
                                        :class="selisihTotal() > 0 ? 'text-green-600' : (selisihTotal() < 0 ? 'text-red-600' :
                                            'text-gray-400')">
                                        <i class="bi"
                                            :class="selisihTotal() > 0 ? 'bi-arrow-up-short' : (selisihTotal() < 0 ?
                                                'bi-arrow-down-short' : '')"></i><span
                                            x-text="fmt(Math.abs(selisihTotal()))"></span>
                                    </p>
                                    <p class="text-[10px] text-gray-400" x-text="persenTotal()"></p>
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div x-show="labels.length" class="h-72 sm:h-80">
                                    <canvas id="chart-perbandingan"></canvas>
                                </div>

                                {{-- ── Rincian angka + filter kategori ──
                                     Data (labels/nilai1/nilai2) sudah ada di Alpine (hasil
                                     fetch muat()), jadi rincian & filternya di-render client-side
                                     di sini juga (bukan lewat <x-rincian-indikator>, yang hanya
                                     untuk data yang tersedia saat render Blade). `pilih` ada di
                                     scope perbandinganBox() (bukan x-data terpisah) supaya
                                     memilih kategori JUGA menyaring chart-nya sendiri lewat
                                     render(), tidak cuma daftar di bawah ini. ── --}}
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <label
                                            class="text-[11px] font-medium text-gray-500 flex items-center gap-1 flex-shrink-0">
                                            <i class="bi bi-funnel text-gray-400"></i> Cari kategori
                                        </label>
                                        <select class="form-select text-xs py-1" x-model="pilih" @change="render()">
                                            <option value="">Semua kategori</option>
                                            <template x-for="l in labelsAsli" :key="l">
                                                <option :value="l" x-text="l"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div
                                        class="border border-gray-100 rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                                        <div
                                            class="grid grid-cols-[1fr_auto_auto_auto] gap-3 px-3 py-1.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                                            <span>Kategori</span>
                                            <span x-text="periode1"></span>
                                            <span x-text="periode2"></span>
                                            <span>Selisih</span>
                                        </div>
                                        <div class="divide-y divide-gray-100">
                                            <template x-for="(l, i) in labels" :key="l">
                                                <div
                                                    class="grid grid-cols-[1fr_auto_auto_auto] gap-3 px-3 py-1.5 text-xs items-center">
                                                    <span class="text-gray-700 font-medium truncate"
                                                        x-text="l"></span>
                                                    <span class="text-gray-600" x-text="fmt(nilai1[i])"></span>
                                                    <span class="text-gray-600" x-text="fmt(nilai2[i])"></span>
                                                    <span class="font-semibold flex-shrink-0"
                                                        :class="(nilai2[i] - nilai1[i]) > 0 ? 'text-green-600' : ((nilai2[i] -
                                                            nilai1[i]) < 0 ? 'text-red-600' : 'text-gray-400')"
                                                        x-text="((nilai2[i] - nilai1[i]) > 0 ? '+' : '') + fmt(nilai2[i] - nilai1[i])"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    <x-slot:scripts>
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
                        await this.$nextTick();
                        if (this.data?.ada_data) {
                            this.donutGender('chart-kpi-ktp', this.data.kpi.wajib_ktp);
                            this.donutGender('chart-kpi-penduduk', this.data.kpi.penduduk);
                            this.donutGender('chart-kpi-kk', this.data.kpi.kk);
                            this.trenChart('chart-tren-penduduk', this.data.tren, 'penduduk', 'jiwa');
                            this.trenChart('chart-tren-kepadatan', this.data.tren, 'kepadatan', 'jiwa/km²');
                        }
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

            function perbandinganBox() {
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
                    waktuId1: @json($defaultWaktuId1),
                    waktuId2: @json($defaultWaktuId2),
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
                        if (!canvas || !this.labels.length) return;

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
                        // Instance-nya disimpan sebagai properti PADA ELEMEN CANVAS
                        // itu sendiri (`canvas._chartInstance`) — BUKAN di closure
                        // JS biasa, dan BUKAN di properti objek reaktif Alpine.
                        // Ditemukan 2026-08-25: closure biasa (`let chartInstance`
                        // di luar `return {...}`) TERBUKTI tidak bisa diandalkan di
                        // sini — nilainya "reset" ke null antar pemanggilan render()
                        // meski secara sintaks JS seharusnya tetap (diverifikasi
                        // lewat simulasi Alpine+Chart.js sungguhan, bukan asumsi).
                        // Menaruhnya di elemen DOM (yang persisten selama canvas itu
                        // tidak dibongkar) menghindari masalah itu sepenuhnya.
                        if (canvas._chartInstance) {
                            canvas._chartInstance.destroy();
                            canvas._chartInstance = null;
                        }

                        canvas._chartInstance = new Chart(canvas, {
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
                    },
                };
            }
        </script>
    </x-slot:scripts>

</x-layouts.public>
