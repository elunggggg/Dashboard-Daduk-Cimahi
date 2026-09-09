{{-- "Perbandingan Antar Periode" — kini bagian grid biasa (bisa dipindah/
     disembunyikan/diurut lewat "Bagian Dashboard"). Widget mandiri: Alpine
     `perbandinganBox()` (di dashboard/_skrip) + endpoint api.dashboard-publik.kategori.
     Butuh: $waktuListPerbandingan, $perbandinganIndikator, $pw1, $pw2. --}}
<x-seksi halaman="dashboard" kunci="perbandingan_kota" judul="Perbandingan Antar Periode" lebar="penuh" :urutan="900">
    <div class="card overflow-hidden" x-data="perbandinganBox({ w1: @js($pw1), w2: @js($pw2) })">
        <div class="p-4 sm:p-5 bg-gradient-to-r from-brand-50 to-white border-b border-gray-100">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-brand-600 text-white flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-bar-chart-line-fill text-lg"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold text-gray-900">Perbandingan Antar Periode</h2>
                    <p class="text-xs text-gray-500">Bandingkan satu indikator antara dua periode data — pilih indikator dan periodenya di bawah.</p>
                </div>
            </div>

            @if ($waktuListPerbandingan->count() >= 2)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="text-[11px] font-semibold text-gray-500 mb-1 block">Indikator</label>
                        <select class="form-select text-sm w-full" x-model="kategori" @change="muat()">
                            @foreach ($perbandinganIndikator as $kunci => $label)
                                <option value="{{ $kunci }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-gray-500 mb-1 block">Periode Pembanding</label>
                        <select class="form-select text-sm w-full" x-model="waktuId1" @change="muat()">
                            @foreach ($waktuListPerbandingan as $w)
                                <option value="{{ $w->id }}">{{ $w->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-gray-500 mb-1 block">Periode Terbaru</label>
                        <select class="form-select text-sm w-full" x-model="waktuId2" @change="muat()">
                            @foreach ($waktuListPerbandingan as $w)
                                <option value="{{ $w->id }}">{{ $w->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif
        </div>

        @if ($waktuListPerbandingan->count() < 2)
            <p class="py-12 text-center text-gray-400 text-sm">
                <i class="bi bi-info-circle"></i> Perbandingan memerlukan minimal dua periode data.
            </p>
        @else
            <div x-show="loading" class="p-4 sm:p-5 space-y-3">
                <div class="skeleton h-40 w-full"></div>
                <div class="skeleton h-3 w-2/3"></div>
                <div class="skeleton h-3 w-1/2"></div>
            </div>
            <div x-show="!loading && ada && !labels.length" x-cloak class="py-16 text-center text-gray-400">
                <i class="bi bi-database-x text-3xl block mb-2"></i>
                Belum ada data untuk kombinasi ini.
            </div>

            <div x-show="!loading && labelsAsli.length" x-cloak>
                <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
                    <div class="p-4 text-center">
                        <p class="text-[11px] text-gray-500 mb-1" x-text="periode1"></p>
                        <p class="text-xl sm:text-2xl font-extrabold text-gray-700 tracking-tight" x-text="fmt(totalPeriode1())"></p>
                    </div>
                    <div class="p-4 text-center bg-brand-50/40">
                        <p class="text-[11px] text-brand-700 font-semibold mb-1" x-text="periode2"></p>
                        <p class="text-xl sm:text-2xl font-extrabold text-brand-800 tracking-tight" x-text="fmt(totalPeriode2())"></p>
                    </div>
                    <div class="p-4 text-center">
                        <p class="text-[11px] text-gray-500 mb-1">Selisih</p>
                        <p class="text-xl sm:text-2xl font-extrabold tracking-tight"
                            :class="selisihTotal() > 0 ? 'text-green-600' : (selisihTotal() < 0 ? 'text-red-600' : 'text-gray-400')">
                            <i class="bi" :class="selisihTotal() > 0 ? 'bi-arrow-up-short' : (selisihTotal() < 0 ? 'bi-arrow-down-short' : '')"></i><span x-text="fmt(Math.abs(selisihTotal()))"></span>
                        </p>
                        <p class="text-[10px] text-gray-400" x-text="persenTotal()"></p>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <div x-show="labels.length" class="h-72 sm:h-80">
                        <canvas id="chart-perbandingan"></canvas>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 mb-2">
                            <label class="text-[11px] font-medium text-gray-500 flex items-center gap-1 flex-shrink-0">
                                <i class="bi bi-funnel text-gray-400"></i> Cari kategori
                            </label>
                            <select class="form-select text-xs py-1" x-model="pilih" @change="render()">
                                <option value="">Semua kategori</option>
                                <template x-for="l in labelsAsli" :key="l">
                                    <option :value="l" x-text="l"></option>
                                </template>
                            </select>
                        </div>
                        <div class="border border-gray-100 rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                            <div class="grid grid-cols-[1fr_auto_auto_auto] gap-3 px-3 py-1.5 text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                                <span>Kategori</span>
                                <span x-text="periode1"></span>
                                <span x-text="periode2"></span>
                                <span>Selisih</span>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <template x-for="(l, i) in labels" :key="l">
                                    <div class="grid grid-cols-[1fr_auto_auto_auto] gap-3 px-3 py-1.5 text-xs items-center">
                                        <span class="text-gray-700 font-medium truncate" x-text="l"></span>
                                        <span class="text-gray-600" x-text="fmt(nilai1[i])"></span>
                                        <span class="text-gray-600" x-text="fmt(nilai2[i])"></span>
                                        <span class="font-semibold flex-shrink-0"
                                            :class="(nilai2[i] - nilai1[i]) > 0 ? 'text-green-600' : ((nilai2[i] - nilai1[i]) < 0 ? 'text-red-600' : 'text-gray-400')"
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
</x-seksi>
