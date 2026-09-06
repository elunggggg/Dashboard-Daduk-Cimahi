<x-layouts.public title="Peta Wilayah">
    <x-slot:head>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    </x-slot:head>

    {{-- Hero strip --}}
    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-extrabold tracking-tight mb-1">Peta Wilayah</h1>
            <p class="text-brand-200 text-sm">
                Distribusi penduduk per kelurahan — Periode: {{ $latestWaktu?->label ?? 'belum ada data' }}
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- ── Filter wilayah ── --}}
        @php
            $wilayahOpsi = $semuaWilayah->map(fn ($w) => [
                'id'        => $w->id,
                'kelurahan' => $w->nama_kelurahan,
                'kecamatan' => $w->nama_kecamatan,
            ])->values();
        @endphp

        <form method="GET" action="{{ route('peta.index') }}" class="card p-4 mb-6"
              x-data="{
                  semua: {{ Illuminate\Support\Js::from($wilayahOpsi) }},
                  kecamatan: @js($kecamatan ?? ''),
                  kelurahan: @js((string) ($kelurahanId ?? '')),
                  get daftarKelurahan() {
                      return this.kecamatan
                          ? this.semua.filter(w => w.kecamatan === this.kecamatan)
                          : this.semua;
                  },
                  onKecamatanChange() {
                      // Kelurahan yang tidak lagi berada di kecamatan terpilih harus direset,
                      // kalau tidak filter akan saling bertabrakan saat dikirim.
                      const masihValid = this.daftarKelurahan.some(w => String(w.id) === this.kelurahan);
                      if (! masihValid) this.kelurahan = '';
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
                    <select name="kelurahan" id="f-kelurahan" class="form-select" x-model="kelurahan">
                        <option value="">Semua Kelurahan</option>
                        <template x-for="w in daftarKelurahan" :key="w.id">
                            <option :value="w.id" x-text="w.kelurahan"></option>
                        </template>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-funnel"></i> Terapkan
                    </button>
                    @if($kecamatan || $kelurahanId)
                        <a href="{{ route('peta.index') }}" class="btn-secondary">Reset</a>
                    @endif
                </div>
            </div>

            {{-- Ringkasan filter aktif --}}
            @if($kecamatan || $kelurahanId)
                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-gray-500">Menampilkan:</span>
                    @if($kelurahanTerpilih)
                        <span class="badge-blue">Kelurahan {{ $kelurahanTerpilih->nama_kelurahan }}</span>
                    @endif
                    @if($kecamatan)
                        <span class="badge-gray">Kec. {{ $kecamatan }}</span>
                    @endif
                    <span class="ml-auto text-gray-500">
                        Total <strong class="text-gray-900">{{ number_format($totalTerpilih, 0, ',', '.') }}</strong> jiwa
                        · {{ $wilayahStats->count() }} kelurahan
                    </span>
                </div>
            @endif
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- Map (2/3) --}}
            <div class="lg:col-span-2 card overflow-hidden">
                <div id="leaflet-map" class="w-full h-[420px] sm:h-[520px]"></div>
            </div>

            {{-- Stats panel (1/3) --}}
            <div class="space-y-4">

                @if($kelurahanTerpilih)
                    {{-- Mode kelurahan tunggal: tampilkan detail kelurahan itu saja,
                         tanpa dibungkus daftar kecamatan. --}}
                    @php
                        $kel      = $wilayahStats->first();
                        $jiwa     = (int) ($kel->total_penduduk ?? 0);
                        $luas     = $kel->luas_km2 ? (float) $kel->luas_km2 : null;
                        $kepadatan = $luas ? $jiwa / $luas : null;
                    @endphp

                    <div class="card p-5">
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center flex-shrink-0">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-base font-bold text-gray-900 leading-tight">{{ $kel->nama_kelurahan }}</p>
                                <p class="text-xs text-gray-400 truncate">Kec. {{ $kel->nama_kecamatan }}</p>
                                <code class="mt-1 inline-block text-[10px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">
                                    {{ $kel->kode_kemendagri }}
                                </code>
                            </div>
                        </div>

                        <div class="space-y-2.5">
                            <div class="flex items-baseline justify-between border-b border-gray-100 pb-2">
                                <span class="text-xs text-gray-500">Jumlah penduduk</span>
                                <span class="text-xl font-extrabold text-gray-900 tracking-tight">
                                    {{ number_format($jiwa, 0, ',', '.') }}
                                    <span class="text-[10px] font-medium text-gray-400">jiwa</span>
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between border-b border-gray-100 pb-2">
                                <span class="text-xs text-gray-500">Luas wilayah</span>
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ $luas ? number_format($luas, 2, ',', '.').' km²' : '—' }}
                                </span>
                            </div>
                            <div class="flex items-baseline justify-between">
                                <span class="text-xs text-gray-500">Kepadatan</span>
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ $kepadatan ? number_format($kepadatan, 0, ',', '.').' jiwa/km²' : '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Jalan keluar dari mode tunggal --}}
                    <a href="{{ route('peta.index', ['kecamatan' => $kel->nama_kecamatan]) }}"
                       class="btn-secondary w-full justify-center">
                        <i class="bi bi-arrow-left"></i>
                        Lihat seluruh Kec. {{ $kel->nama_kecamatan }}
                    </a>

                @else

                @forelse($kecamatanStats as $kec => $stat)
                    <div class="card p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-2 h-8 bg-brand-600 rounded-full flex-shrink-0"></div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ $kec }}</p>
                                <p class="text-xs text-gray-400">{{ $stat['jumlah_kelurahan'] }} kelurahan</p>
                            </div>
                            <div class="ml-auto text-right flex-shrink-0">
                                <p class="text-lg font-extrabold text-gray-900 tracking-tight">
                                    {{ number_format($stat['total_penduduk'], 0, ',', '.') }}
                                </p>
                                <p class="text-[10px] text-gray-400">jiwa</p>
                            </div>
                        </div>

                        {{-- Per-kelurahan breakdown --}}
                        <div class="space-y-1.5 pl-4 border-l-2 border-gray-100">
                            @foreach($stat['kelurahan'] as $kel)
                                <a href="{{ route('peta.index', ['kelurahan' => $kel->id]) }}"
                                   class="flex items-center justify-between text-xs rounded px-1 -mx-1 py-0.5
                                          {{ $kelurahanId === $kel->id ? 'bg-brand-50 text-brand-800 font-semibold' : 'hover:bg-gray-50' }}">
                                    <span class="truncate {{ $kelurahanId === $kel->id ? '' : 'text-gray-600' }}">{{ $kel->nama_kelurahan }}</span>
                                    <span class="font-medium flex-shrink-0 ml-2 {{ $kelurahanId === $kel->id ? '' : 'text-gray-800' }}">
                                        {{ number_format($kel->total_penduduk ?? 0, 0, ',', '.') }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="card p-6 text-center text-sm text-gray-400">
                        <i class="bi bi-search text-2xl block mb-2"></i>
                        Tidak ada wilayah yang cocok dengan filter.
                    </div>
                @endforelse

                @endif

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
        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('leaflet-map').setView([-6.875, 107.541], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(map);

            // Choropleth berbasis wilayah_id (kelurahan) — kunci ke id, bukan
            // nama, supaya tidak rusak oleh ejaan berbeda antar sumber data.
            const pendudukPerWilayah = @json($pendudukPerWilayah);
            const pendudukPerKecamatan = @json($kecamatanPenuh);
            const fokusKec       = @js($kecamatan);
            const fokusWilayahId = @js($kelurahanTerpilih?->id);
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

            Promise.all([
                fetch('/geojson/batas-kecamatan-cimahi.geojson').then(r => r.json()),
                fetch('/geojson/batas-kelurahan-cimahi.geojson').then(r => r.json()),
                fetch('/geojson/batas-rw-cimahi.geojson').then(r => r.json()),
            ]).then(([kecamatanGeo, kelurahanGeo, rwGeo]) => {

                const lapisanKecamatan = L.geoJSON(kecamatanGeo, {
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

                const lapisanKelurahan = L.geoJSON(kelurahanGeo, {
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
