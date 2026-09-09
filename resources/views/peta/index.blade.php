<x-layouts.public title="Peta Wilayah">
    <x-slot:head>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    </x-slot:head>

    {{-- Hero strip --}}
    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard
                    Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Peta Wilayah</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight mb-1">Peta Wilayah</h1>
            <p class="text-brand-200 text-sm">
                Distribusi penduduk per kelurahan — Periode: {{ $latestWaktu?->label ?? 'belum ada data' }}
            </p>
        </div>
    </div>

    {{-- ── Phase 5: filter kecamatan/kelurahan tanpa reload ──
         GeoJSON & choropleth dimuat SEKALI (tidak berubah oleh filter ini —
         peta selalu periode terbaru), jadi fetch() hanya mengambil ulang panel
         statistik (stats_html, dirender di server, lihat peta/_stats.blade.php)
         lalu me-restyle layer peta yang sudah ada lewat window._petaRestyle. --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6"
         x-data="petaApp({
             kecamatan: @js($kecamatan ?? ''),
             kelurahan_id: @js($kelurahanId),
             total_terpilih: @js($totalTerpilih),
             jumlah_kelurahan: @js($wilayahStats->count()),
             kelurahan_nama: @js($kelurahanTerpilih?->nama_kelurahan),
             stats_html: @js((string) view('peta._stats', [
                 'wilayahStats' => $wilayahStats, 'kecamatanStats' => $kecamatanStats,
                 'kelurahanId' => $kelurahanId, 'kelurahanTerpilih' => $kelurahanTerpilih,
             ])->render()),
         })"
    >

        {{-- ── Filter wilayah ── --}}
        @php
            $wilayahOpsi = $semuaWilayah->map(fn ($w) => [
                'id'        => $w->id,
                'kelurahan' => $w->nama_kelurahan,
                'kecamatan' => $w->nama_kecamatan,
            ])->values();
        @endphp

        <div class="card p-4 mb-6"
              x-data="{
                  semua: {{ Illuminate\Support\Js::from($wilayahOpsi) }},
                  get daftarKelurahan() {
                      return kecamatan
                          ? this.semua.filter(w => w.kecamatan === kecamatan)
                          : this.semua;
                  },
                  onKecamatanChange() {
                      // Kelurahan yang tidak lagi berada di kecamatan terpilih harus direset,
                      // kalau tidak filter akan saling bertabrakan saat dikirim.
                      const masihValid = this.daftarKelurahan.some(w => String(w.id) === kelurahan);
                      if (! masihValid) kelurahan = '';
                      muat();
                  },
              }">

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                <div>
                    <label for="f-kecamatan" class="form-label">Kecamatan</label>
                    <select name="kecamatan" id="f-kecamatan" class="form-select"
                            x-model="kecamatan" @change="onKecamatanChange()">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatanList as $kec)
                            <option value="{{ $kec }}">{{ $kec }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="f-kelurahan" class="form-label">Kelurahan</label>
                    <select name="kelurahan" id="f-kelurahan" class="form-select" x-model="kelurahan" @change="muat()">
                        <option value="">Semua Kelurahan</option>
                        <template x-for="w in daftarKelurahan" :key="w.id">
                            <option :value="w.id" x-text="w.kelurahan"></option>
                        </template>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" class="btn-primary" @click="muat()">
                        <i class="bi bi-funnel"></i> Terapkan
                    </button>
                    <button type="button" class="btn-secondary" x-show="kecamatan || kelurahan" x-cloak
                            @click="kecamatan = ''; kelurahan = ''; muat()">
                        Reset
                    </button>
                </div>
            </div>

            {{-- Ringkasan filter aktif --}}
            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2 text-sm"
                 x-show="kecamatan || kelurahan" x-cloak>
                <span class="text-gray-500">Menampilkan:</span>
                <span class="badge-blue" x-show="data.kelurahan_nama" x-text="'Kelurahan ' + data.kelurahan_nama"></span>
                <span class="badge-gray" x-show="kecamatan" x-text="'Kec. ' + kecamatan"></span>
                <span class="ml-auto text-gray-500">
                    Total <strong class="text-gray-900" x-text="fmt(data.total_terpilih)"></strong> jiwa
                    · <span x-text="data.jumlah_kelurahan"></span> kelurahan
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- Map (2/3). `isolate` (+ z-0) mengurung z-index internal Leaflet
                 (kontrol/pane bisa sampai ~1000) dalam stacking context sendiri,
                 supaya TIDAK menimpa navbar sticky (z-40) saat halaman digulir. --}}
            <div class="lg:col-span-2 card overflow-hidden relative isolate z-0">
                <div id="leaflet-map" class="w-full h-[420px] sm:h-[520px]"></div>
                <div x-show="loading" x-cloak
                     class="absolute inset-0 bg-white/60 flex items-center justify-center z-[1000]">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl text-brand-700"></i>
                </div>
            </div>

            {{-- Stats panel (1/3) — HTML dirender server, diganti utuh saat filter berubah. --}}
            <div class="space-y-4">
                <template x-if="loading">
                    <div class="space-y-4">
                        <x-skeleton-card :chart="false" :rows="3" />
                    </div>
                </template>
                <div x-show="!loading" x-html="statsHtml"></div>

                {{-- Disclaimer --}}
                <div class="alert-info text-xs">
                    <i class="bi bi-info-circle mr-1"></i>
                    Batas RW, kelurahan, dan kecamatan memakai data geospasial BIG/BPS
                    (deliniasi 2024). Warna peta (choropleth) mengikuti jumlah penduduk per
                    <strong>kelurahan</strong> karena data agregat tercatat di tingkat itu, bukan per RW.
                    Gunakan kontrol lapisan di pojok kanan atas peta untuk menyalakan/mematikan
                    tiap batas.
                </div>
            </div>
        </div>
    </div>

    <x-slot:scripts>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
        // ── x-data utama: filter kecamatan/kelurahan tanpa reload (Phase 5) ──
        // Peta Leaflet-nya sendiri (GeoJSON, choropleth) dibangun SEKALI di
        // luar Alpine (lihat DOMContentLoaded di bawah) — komponen ini cuma
        // mengganti panel statistik + memicu window._petaRestyle() untuk
        // mewarnai-ulang layer yang sudah ada, bukan memuat ulang peta.
        function petaApp(seed) {
            return {
                kecamatan: seed.kecamatan ?? '',
                kelurahan: seed.kelurahan_id ? String(seed.kelurahan_id) : '',
                data: seed,
                statsHtml: null, // diisi setelah DOMContentLoaded (lihat init()) atau fetch()
                loading: false,

                init() {
                    // Panel statistik kunjungan pertama sudah dirender di server
                    // dan dikirim lewat seed (stats_html) — dipakai langsung,
                    // TIDAK fetch ulang hanya untuk menampilkan yang sudah ada.
                    this.statsHtml = seed.stats_html;
                },

                fmt(n) { return window.formatAngka(n); },

                async muat() {
                    this.loading = true;

                    const params = new URLSearchParams();
                    if (this.kecamatan) params.set('kecamatan', this.kecamatan);
                    if (this.kelurahan) params.set('kelurahan', this.kelurahan);

                    const url = '{{ route('peta.index') }}' + (params.toString() ? '?' + params.toString() : '');
                    window.history.pushState({}, '', url);

                    try {
                        const res = await fetch(url, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const json = await res.json();

                        this.data = json;
                        this.statsHtml = json.stats_html;
                        // Server bisa mengoreksi filter (mis. kelurahan tanpa
                        // kecamatan otomatis menurunkan kecamatannya) — sinkronkan balik.
                        this.kecamatan = json.kecamatan ?? '';
                        this.kelurahan = json.kelurahan_id ? String(json.kelurahan_id) : '';

                        window._petaRestyle && window._petaRestyle(this.kecamatan || null, json.kelurahan_id ?? null);
                    } catch (e) {
                        console.error('Gagal memuat data Peta', e);
                    }

                    this.loading = false;
                },
            };
        }

        // Dipanggil dari tombol di peta/_stats.blade.php ("Lihat seluruh Kec.
        // ...", baris kelurahan) — keduanya sudah tidak berupa <a href> supaya
        // tidak memicu reload; ini menembus ke instance Alpine terdekat.
        window._petaGantiFilter = function (kecamatan, kelurahanId) {
            const el = document.querySelector('[x-data^="petaApp"]');
            if (!el || !window.Alpine) return;
            const app = window.Alpine.$data(el);
            app.kecamatan = kecamatan || '';
            app.kelurahan = kelurahanId ? String(kelurahanId) : '';
            app.muat();
        };

        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('leaflet-map').setView([-6.875, 107.541], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(map);

            // Choropleth berbasis wilayah_id (kelurahan) — kunci ke id, bukan
            // nama, supaya tidak rusak oleh ejaan berbeda antar sumber data.
            // TIDAK berubah oleh filter kecamatan/kelurahan (selalu periode
            // terbaru, seluruh kota) — jadi aman dimuat sekali saja di sini.
            const pendudukPerWilayah = @json($pendudukPerWilayah);
            const pendudukPerKecamatan = @json($kecamatanPenuh);
            let fokusKec       = @js($kecamatan);
            let fokusWilayahId = @js($kelurahanTerpilih?->id);
            const minPop = Math.min(...Object.values(pendudukPerWilayah));
            const maxPop = Math.max(...Object.values(pendudukPerWilayah), 1);
            const fmt    = (n) => new Intl.NumberFormat('id-ID').format(n ?? 0);

            // Gradasi kontinu kuning muda (sedikit) → merah tua (banyak) —
            // interpolasi RGB linear, tanpa library tambahan.
            const GRADASI_MUDA = [254, 249, 195]; // #FEF9C3
            const GRADASI_TUA  = [153, 27, 27];    // #991B1B
            function choroplethColor(pop) {
                const rentang = maxPop - minPop;
                const t = rentang > 0 ? (pop - minPop) / rentang : 0;
                const rgb = GRADASI_MUDA.map((c, i) => Math.round(c + (GRADASI_TUA[i] - c) * t));
                return `rgb(${rgb.join(',')})`;
            }

            function dalamFilter(props) {
                if (fokusWilayahId) return props.wilayah_id === fokusWilayahId;
                if (fokusKec)       return props.kecamatan === fokusKec;
                return true;
            }

            // ── Lapisan Kelurahan (choropleth, tengah) ──────────────────────
            function gayaKelurahan(feature) {
                const aktif = dalamFilter(feature.properties);
                return {
                    fillColor:   choroplethColor(pendudukPerWilayah[feature.properties.wilayah_id] ?? 0),
                    weight:      aktif && (fokusKec || fokusWilayahId) ? 3 : 2,
                    opacity:     1,
                    color:       '#1e3a5f',
                    fillOpacity: aktif ? 0.75 : 0.18,
                };
            }

            // ── Lapisan Kecamatan (outline paling tebal, atas) ──────────────
            function gayaKecamatan(feature) {
                const aktif = ! fokusKec || feature.properties.kecamatan === fokusKec;
                return {
                    fillOpacity: 0,
                    weight: 4,
                    opacity: aktif ? 1 : 0.35,
                    color: '#0f172a',
                };
            }

            // ── Lapisan RW (outline tipis, ringan) ──────────────────────────
            function gayaRw(feature) {
                return {
                    fillOpacity: 0,
                    weight: 1,
                    opacity: 0.6,
                    color: '#f97316',
                    dashArray: '3,3',
                };
            }

            const layerControl = L.control.layers(null, null, { collapsed: false }).addTo(map);

            // ── Legenda gradasi warna (pojok kiri-bawah) ────────────────────
            const legenda = L.control({ position: 'bottomleft' });
            legenda.onAdd = function () {
                const div = L.DomUtil.create('div', 'leaflet-bar');
                div.style.cssText = 'background:#fff;padding:8px 10px;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.3);font:11px Inter,system-ui,sans-serif;color:#1e293b;';
                div.innerHTML = `
                    <div style="font-weight:600;margin-bottom:4px;">Jumlah Penduduk</div>
                    <div style="height:10px;width:140px;border-radius:4px;background:linear-gradient(to right, rgb(${GRADASI_MUDA.join(',')}), rgb(${GRADASI_TUA.join(',')}));"></div>
                    <div style="display:flex;justify-content:space-between;margin-top:2px;color:#64748b;">
                        <span>${fmt(minPop)}</span><span>${fmt(maxPop)}</span>
                    </div>
                `;
                return div;
            };
            legenda.addTo(map);

            let lapisanKelurahan = null;
            let lapisanKecamatan = null;

            // Restyle + refit tanpa reload — dipanggil petaApp.muat() setelah
            // fetch selesai. Tidak menyentuh jaringan sama sekali: GeoJSON dan
            // choropleth-nya sudah ada di memori sejak Promise.all di bawah.
            window._petaRestyle = function (kecBaru, wilayahIdBaru) {
                fokusKec = kecBaru;
                fokusWilayahId = wilayahIdBaru;

                if (!lapisanKelurahan || !lapisanKecamatan) return;

                let lapisanFokus = null;
                lapisanKelurahan.eachLayer((lyr) => {
                    lyr.setStyle(gayaKelurahan(lyr.feature));
                    if (fokusWilayahId && lyr.feature.properties.wilayah_id === fokusWilayahId) {
                        lapisanFokus = lyr;
                    }
                });
                lapisanKecamatan.eachLayer((lyr) => lyr.setStyle(gayaKecamatan(lyr.feature)));

                if (lapisanFokus) {
                    map.fitBounds(lapisanFokus.getBounds(), { padding: [24, 24] });
                    lapisanFokus.openPopup();
                } else if (fokusKec) {
                    const bataKec = Object.values(lapisanKecamatan._layers)
                        .find((l) => l.feature.properties.kecamatan === fokusKec);
                    if (bataKec) map.fitBounds(bataKec.getBounds(), { padding: [24, 24] });
                } else if (lapisanKelurahan.getBounds().isValid()) {
                    map.fitBounds(lapisanKelurahan.getBounds(), { padding: [16, 16] });
                }
            };

            Promise.all([
                fetch('/geojson/batas-kecamatan-cimahi.geojson').then(r => r.json()),
                fetch('/geojson/batas-kelurahan-cimahi.geojson').then(r => r.json()),
                fetch('/geojson/batas-rw-cimahi.geojson').then(r => r.json()),
            ]).then(([kecamatanGeo, kelurahanGeo, rwGeo]) => {

                lapisanKecamatan = L.geoJSON(kecamatanGeo, {
                    style: gayaKecamatan,
                    onEachFeature: (feature, lyr) => {
                        const nama  = feature.properties.kecamatan;
                        const total = pendudukPerKecamatan[nama] ?? 0;
                        lyr.bindPopup(
                            `<strong>Kec. ${nama}</strong><br>` +
                            `Penduduk: <strong>${fmt(total)}</strong> jiwa<br>` +
                            `${feature.properties.jumlah_rw} RW`
                        );
                    },
                }).addTo(map);

                let lapisanFokus = null;

                lapisanKelurahan = L.geoJSON(kelurahanGeo, {
                    style: gayaKelurahan,
                    onEachFeature: (feature, lyr) => {
                        const { wilayah_id, kelurahan, kecamatan, jumlah_rw } = feature.properties;
                        const pop = pendudukPerWilayah[wilayah_id] ?? 0;

                        lyr.bindPopup(
                            `<strong>Kel. ${kelurahan}</strong><br>` +
                            `Kec. ${kecamatan}<br>` +
                            `Penduduk: <strong>${fmt(pop)}</strong> jiwa<br>` +
                            `${jumlah_rw} RW`
                        );

                        lyr.on('mouseover', () => lyr.setStyle({ fillOpacity: 0.9 }));
                        lyr.on('mouseout',  () => lyr.setStyle(gayaKelurahan(feature)));

                        if (fokusWilayahId && wilayah_id === fokusWilayahId) {
                            lapisanFokus = lyr;
                        }
                    },
                }).addTo(map);

                const lapisanRw = L.geoJSON(rwGeo, {
                    style: gayaRw,
                    onEachFeature: (feature, lyr) => {
                        const { rw, kelurahan, kecamatan } = feature.properties;
                        lyr.bindPopup(
                            `<strong>${rw}</strong><br>` +
                            `Kel. ${kelurahan}<br>` +
                            `Kec. ${kecamatan}`
                        );
                    },
                });
                // RW sengaja TIDAK auto-tampil (312 poligon bisa memenuhi peta) —
                // tersedia lewat kontrol lapisan di pojok kanan atas.

                layerControl.addOverlay(lapisanKecamatan, 'Batas Kecamatan');
                layerControl.addOverlay(lapisanKelurahan, 'Batas Kelurahan');
                layerControl.addOverlay(lapisanRw, 'Batas RW');

                // Zoom ke kelurahan/kecamatan terpilih; kalau tidak ada filter, tampilkan seluruh kota
                if (lapisanFokus) {
                    map.fitBounds(lapisanFokus.getBounds(), { padding: [24, 24] });
                    lapisanFokus.openPopup();
                } else if (fokusKec) {
                    const bataKec = Object.values(lapisanKecamatan._layers)
                        .find(l => l.feature.properties.kecamatan === fokusKec);
                    if (bataKec) map.fitBounds(bataKec.getBounds(), { padding: [24, 24] });
                } else if (lapisanKelurahan.getBounds().isValid()) {
                    map.fitBounds(lapisanKelurahan.getBounds(), { padding: [16, 16] });
                }
            }).catch((e) => console.warn('GeoJSON belum tersedia atau tidak valid', e));
        });
        </script>
    </x-slot:scripts>

</x-layouts.public>
