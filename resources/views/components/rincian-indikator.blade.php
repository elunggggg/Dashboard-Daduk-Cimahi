{{-- Rincian angka di luar chart + filter satu kategori + toggle "Lihat Tabel".
     Filter di sini BUKAN cuma menyaring tabel — lewat `chartId` (opsional,
     wajib diisi kalau ada canvas Chart.js untuk indikator ini) chart-nya
     SENDIRI juga diperbarui untuk hanya menampilkan kategori yang dipilih
     (Chart.getChart()). Tabel default tersembunyi, muncul saat "Lihat Tabel"
     diklik. --}}
@props(['data', 'total' => null, 'satuan' => 'jiwa', 'chartId' => null])
@php
    $rows = collect($data)->map(fn ($v, $k) => ['label' => (string) $k, 'jumlah' => (int) $v])->values();
@endphp
@if ($rows->isNotEmpty())
    <div
        x-data="{
            pilih: '',
            tabelOpen: false,
            labelsAsli: @js($rows->pluck('label')),
            nilaiAsli: @js($rows->pluck('jumlah')),
            terapkanKeChart() {
                const chartId = @js($chartId);
                if (! chartId || ! window.Chart) return;
                const chart = window.Chart.getChart(chartId);
                if (! chart) return;
                if (this.pilih === '') {
                    chart.data.labels = this.labelsAsli;
                    chart.data.datasets.forEach((ds) => { ds.data = this.nilaiAsli; });
                } else {
                    const idx = this.labelsAsli.indexOf(this.pilih);
                    chart.data.labels = [this.pilih];
                    chart.data.datasets.forEach((ds) => { ds.data = [this.nilaiAsli[idx]]; });
                }
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

        <div x-show="tabelOpen" x-cloak x-transition.opacity.duration.200ms class="mt-2">
            <div class="flex items-center gap-2 mb-2">
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
                            <tr x-show="pilih === '' || pilih === @js($r['label'])"
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
