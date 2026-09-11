@props([
    'action'         => null,
    'kecamatanList'  => collect(),
    'wilayahList'    => collect(),
    'waktuList'      => collect(),
    'kecamatan'      => null,
    'wilayahId'      => null,
    'waktuId'        => null,
    'showWaktu'      => true,
    'submitLabel'    => 'Terapkan',
])

{{-- Filter wilayah/periode — FORM GET biasa: menekan "Terapkan" memuat ulang
     halaman dengan query string (bukan AJAX). Alpine di sini HANYA untuk
     cascade Kecamatan → daftar Kelurahan; nilai terpilih dikirim lewat name
     select saat submit. --}}
<form method="GET" action="{{ $action }}" class="card p-4"
    x-data="{
        selectedKecamatan: @js($kecamatan ?? ''),
        selectedWilayah: @js($wilayahId ? (string) $wilayahId : ''),
        wilayahAll: @js($wilayahList->values()),
        get filteredKelurahan() {
            return this.selectedKecamatan
                ? this.wilayahAll.filter(w => w.nama_kecamatan === this.selectedKecamatan)
                : this.wilayahAll;
        },
        onKecamatanChange() {
            // Kelurahan yang tidak lagi berada di kecamatan terpilih direset.
            if (! this.filteredKelurahan.some(w => String(w.id) === this.selectedWilayah)) {
                this.selectedWilayah = '';
            }
        },
    }"
>
    <div class="flex flex-wrap items-end gap-3">

        {{-- Kecamatan --}}
        <div class="flex-1 min-w-[180px]">
            <label class="form-label text-xs">Kecamatan</label>
            <select name="kecamatan" x-model="selectedKecamatan" @change="onKecamatanChange()" class="form-select text-sm">
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatanList as $kec)
                    <option value="{{ $kec }}" @selected(($kecamatan ?? '') === $kec)>{{ $kec }}</option>
                @endforeach
            </select>
        </div>

        {{-- Kelurahan (cascade) --}}
        <div class="flex-1 min-w-[180px]">
            <label class="form-label text-xs">Kelurahan</label>
            <select name="wilayah_id" x-model="selectedWilayah" class="form-select text-sm">
                <option value="">Semua Kelurahan</option>
                <template x-for="w in filteredKelurahan" :key="w.id">
                    <option :value="w.id" x-text="w.nama_kelurahan"></option>
                </template>
            </select>
        </div>

        {{-- Periode — hanya dirender bila ADA yang bisa dipilih. SENGAJA tanpa
             opsi "Semua Periode": komponen ini dipakai halaman berangka STOK
             (Demografi/Sosial/Dashboard); menjumlahkan stok antar semester
             menghitung orang yang sama berulang kali. --}}
        @if($showWaktu && $waktuList->count() > 1)
            <div class="flex-1 min-w-[150px]">
                <label class="form-label text-xs">Periode</label>
                <select name="waktu_id" class="form-select text-sm">
                    @foreach($waktuList as $waktu)
                        <option value="{{ $waktu->id }}" @selected((string) $waktuId === (string) $waktu->id)>{{ $waktu->label }}</option>
                    @endforeach
                </select>
            </div>
        @elseif($showWaktu && $waktuList->count() === 1)
            <div class="flex-1 min-w-[150px]">
                <span class="form-label text-xs">Periode</span>
                <p class="text-sm font-semibold text-gray-700 py-2">{{ $waktuList->first()->label }}</p>
            </div>
        @endif

        <button type="submit" class="btn-primary py-2">
            <i class="bi bi-funnel text-xs"></i>
            {{ $submitLabel }}
        </button>

        @if($kecamatan || $wilayahId)
            <a href="{{ $action }}" class="btn-secondary py-2">
                <i class="bi bi-x-circle text-xs"></i>
                Reset
            </a>
        @endif

    </div>
</form>
