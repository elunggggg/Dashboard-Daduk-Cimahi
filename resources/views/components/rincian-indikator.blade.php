{{-- Rincian angka di luar chart + filter kategori + toggle "Lihat Tabel".
     Filter di sini BUKAN cuma menyaring tabel — lewat `chartId` (opsional,
     wajib diisi kalau ada canvas Chart.js untuk indikator ini) chart-nya
     SENDIRI juga diperbarui untuk hanya menampilkan kategori yang lolos filter
     (Chart.getChart()). Tabel default TERBUKA.

     `range` (opsional): tampilkan juga filter RENTANG angka — cocok untuk
     indikator yang labelnya memuat angka (mis. "Umur 17 Tahun"). Baris yang
     ditampilkan = yang angkanya di dalam rentang; totalnya dihitung otomatis. --}}
@props(['data', 'total' => null, 'satuan' => 'jiwa', 'chartId' => null, 'range' => false])
@php
    $rows = collect($data)->map(fn ($v, $k) => ['label' => (string) $k, 'jumlah' => (int) $v])->values();
@endphp
@if ($rows->isNotEmpty())
    <div
        x-data="{
            pilih: '',
            tabelOpen: true,
            rDari: '',
            rSampai: '',
            labelsAsli: @js($rows->pluck('label')),
            nilaiAsli: @js($rows->pluck('jumlah')),

            angkaLabel(l) {
                const m = String(l).match(/-?\d+/);
                return m ? parseInt(m[0], 10) : null;
            },
            dalamRentang(l) {
                if (this.rDari === '' && this.rSampai === '') return true;
                const n = this.angkaLabel(l);
                if (n === null) return false;
                if (this.rDari !== '' && n < Number(this.rDari)) return false;
                if (this.rSampai !== '' && n > Number(this.rSampai)) return false;
                return true;
            },
            lolos(l) {
                return (this.pilih === '' || this.pilih === l) && this.dalamRentang(l);
            },
            get rentang() {
                let total = 0, jumlahKategori = 0;
                this.labelsAsli.forEach((l, i) => {
                    if (this.lolos(l)) { total += this.nilaiAsli[i]; jumlahKategori++; }
                });
                return { total, jumlahKategori };
            },
            terapkanKeChart() {
                const chartId = @js($chartId);
                if (! chartId || ! window.Chart) return;
                const chart = window.Chart.getChart(chartId);
                if (! chart) return;
                const idx = [];
                this.labelsAsli.forEach((l, i) => { if (this.lolos(l)) idx.push(i); });
                chart.data.labels = idx.map((i) => this.labelsAsli[i]);
                chart.data.datasets.forEach((ds) => { ds.data = idx.map((i) => this.nilaiAsli[i]); });
                chart.update();
            },
        }"
        class="mt-3 pt-3 border-t border-gray-100"
    >
        <button type="button" @click="tabelOpen = !tabelOpen"
            class="flex items-center gap-1.5 text-[11px] font-semibold text-brand-700 hover:text-brand-800">
            <i class="bi text-[10px]" :class="tabelOpen ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
            Lihat Tabel
            <span class="text-gray-400 font-normal">({{ $rows->count() }} kategori)</span>
        </button>

        <div x-show="tabelOpen" x-cloak x-transition.opacity.duration.200ms class="mt-2 space-y-2">
            <div class="flex items-center gap-2">
                <label class="text-[11px] font-medium text-gray-500 flex items-center gap-1 flex-shrink-0">
                    <i class="bi bi-funnel text-gray-400"></i> Cari kategori
                </label>
                <select class="form-select text-xs py-1" x-model="pilih" @change="terapkanKeChart()">
                    <option value="">Semua kategori ({{ $rows->count() }})</option>
                    @foreach ($rows as $r)
                        <option value="{{ $r['label'] }}">{{ $r['label'] }}</option>
                    @endforeach
                </select>
            </div>

            @if ($range)
                <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                    <span class="flex items-center gap-1 flex-shrink-0">
                        <i class="bi bi-arrows-expand-vertical text-gray-400 rotate-90"></i> Rentang angka
                    </span>
                    <input type="number" x-model="rDari" placeholder="dari" min="0"
                           class="form-input text-xs py-1 w-20" @input.debounce.200ms="terapkanKeChart()">
                    <span>&ndash;</span>
                    <input type="number" x-model="rSampai" placeholder="sampai" min="0"
                           class="form-input text-xs py-1 w-20" @input.debounce.200ms="terapkanKeChart()">
                    <button type="button" x-show="rDari !== '' || rSampai !== ''" x-cloak
                            @click="rDari = ''; rSampai = ''; terapkanKeChart()"
                            class="text-brand-700 hover:underline flex items-center gap-1">
                        <i class="bi bi-x-circle"></i> reset rentang
                    </button>
                    <span x-show="rDari !== '' || rSampai !== ''" x-cloak
                          class="ml-auto font-semibold text-gray-800">
                        Total rentang:
                        <span x-text="window.formatAngka(rentang.total)"></span> {{ $satuan }}
                        <span class="font-normal text-gray-400">(<span x-text="rentang.jumlahKategori"></span> kategori)</span>
                    </span>
                </div>
            @endif

            <div class="overflow-auto rounded-lg border border-gray-100 max-h-56">
                <table class="w-full text-xs">
                    <thead class="sticky top-0">
                        <tr class="bg-brand-900 text-white">
                            <th class="px-3 py-2 text-left font-semibold">Kategori</th>
                            <th class="px-3 py-2 text-right font-semibold">Jumlah</th>
                            @if ($total)
                                <th class="px-3 py-2 text-right font-semibold">Persentase</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rows as $i => $r)
                            @php $pct = $total ? round($r['jumlah'] / max((int) $total, 1) * 100, 2) : null; @endphp
                            <tr x-show="lolos(@js($r['label']))"
                                class="{{ $i % 2 === 1 ? 'bg-gray-50' : 'bg-white' }}">
                                <td class="px-3 py-1.5 text-gray-700 truncate">{{ $r['label'] }}</td>
                                <td class="px-3 py-1.5 text-right font-semibold text-gray-900">
                                    {{ number_format($r['jumlah'], 0, ',', '.') }} {{ $satuan }}
                                </td>
                                @if ($total)
                                    <td class="px-3 py-1.5 text-right text-gray-500">
                                        {{ number_format($pct, 2, ',', '.') }}%
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
