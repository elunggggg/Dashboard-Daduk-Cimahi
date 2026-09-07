@props([
    'kecamatanList'  => collect(),
    'wilayahList'    => collect(),
    'waktuList'      => collect(),
    'kecamatan'      => null,
    'wilayahId'      => null,
    'waktuId'        => null,
    'showWaktu'      => true,
    'submitLabel'    => 'Tampilkan',
])

{{-- Phase 5: tanpa reload halaman. Komponen ini TIDAK tahu-menahu soal
     fetch/endpoint apa pun — begitu filter diterapkan atau direset ia cuma
     memancarkan custom event `filter-berubah` ke window berisi nilai filter
     terkini, dan halaman pemakainya (sosialApp()/demografiApp(), lihat
     masing-masing Blade) yang mendengarkan event itu lalu melakukan fetch. --}}
<div
    x-data="{
        selectedKecamatan: @js($kecamatan ?? ''),
        selectedWilayah: @js($wilayahId ? (string) $wilayahId : ''),
        selectedWaktu: @js($waktuId ? (string) $waktuId : ''),
        wilayahAll: @js($wilayahList->values()),
        get filteredKelurahan() {
            if (!this.selectedKecamatan) return this.wilayahAll;
            return this.wilayahAll.filter(w => w.nama_kecamatan === this.selectedKecamatan);
        },
        onKecamatanChange() {
            // Kelurahan yang tidak lagi berada di kecamatan terpilih harus direset,
            // kalau tidak filter akan saling bertabrakan saat dikirim.
            const masihValid = this.filteredKelurahan.some(w => String(w.id) === this.selectedWilayah);
            if (! masihValid) this.selectedWilayah = '';
        },
        terapkan() {
            this.$dispatch('filter-berubah', {
                kecamatan: this.selectedKecamatan || null,
                wilayah_id: this.selectedWilayah || null,
                waktu_id: this.selectedWaktu || null,
            });
        },
        reset() {
            this.selectedKecamatan = '';
            this.selectedWilayah = '';
            this.selectedWaktu = '';
            this.terapkan();
        },
    }"
    class="card p-4"
>
    <div class="flex flex-wrap items-end gap-3">

        {{-- Kecamatan --}}
        <div class="flex-1 min-w-[180px]">
            <label class="form-label text-xs">Kecamatan</label>
            <select
                x-model="selectedKecamatan"
                @change="onKecamatanChange()"
                class="form-select text-sm"
            >
                <option value="">Semua Kecamatan</option>
                @foreach($kecamatanList as $kec)
                    <option value="{{ $kec }}">{{ $kec }}</option>
                @endforeach
            </select>
        </div>

        {{-- Kelurahan (cascade) --}}
        <div class="flex-1 min-w-[180px]">
            <label class="form-label text-xs">Kelurahan</label>
            <select x-model="selectedWilayah" class="form-select text-sm">
                <option value="">Semua Kelurahan</option>
                <template x-for="w in filteredKelurahan" :key="w.id">
                    <option :value="w.id" x-text="w.nama_kelurahan"></option>
                </template>
            </select>
        </div>

        {{-- Periode — hanya dirender bila memang ADA yang bisa dipilih.
             Dengan 0 periode dropdown-nya kosong sama sekali, dengan 1 periode
             satu-satunya pilihannya adalah keadaan yang sudah tampil: dua-duanya
             kontrol yang tidak bisa mengubah apa pun. --}}
        @if($showWaktu && $waktuList->count() > 1)
            <div class="flex-1 min-w-[150px]">
                <label class="form-label text-xs">Periode</label>
                {{-- SENGAJA tidak ada opsi "Semua Periode" di sini. Komponen ini
                     hanya dipakai halaman berangka STOK (Dashboard, Demografi,
                     Sosial) — penduduk, KK, KTP, pendidikan, agama. Menjumlahkan
                     stok antar semester menghitung orang yang sama berulang kali,
                     jadi pilihan yang hasilnya selalu salah lebih baik tidak
                     ditawarkan sama sekali daripada ditawarkan dengan peringatan.
                     Halaman berangka ARUS (Mobilitas) memakai filternya sendiri,
                     dan Ekspor boleh karena menulis baris mentah tanpa meringkas.
                     Lihat FilterWilayahService::periodeTerpilih($bolehSemua). --}}
                <select x-model="selectedWaktu" class="form-select text-sm">
                    @foreach($waktuList as $waktu)
                        <option value="{{ $waktu->id }}">{{ $waktu->label }}</option>
                    @endforeach
                </select>
            </div>
        @elseif($showWaktu && $waktuList->count() === 1)
            {{-- Satu-satunya periode: ditampilkan sebagai keterangan, bukan kontrol. --}}
            <div class="flex-1 min-w-[150px]">
                <span class="form-label text-xs">Periode</span>
                <p class="text-sm font-semibold text-gray-700 py-2">{{ $waktuList->first()->label }}</p>
            </div>
        @endif

        <button type="button" @click="terapkan()" class="btn-primary py-2">
            <i class="bi bi-funnel text-xs"></i>
            {{ $submitLabel }}
        </button>

        <button type="button" @click="reset()" class="btn-secondary py-2"
                x-show="selectedKecamatan || selectedWilayah"
                x-cloak>
            <i class="bi bi-x-circle text-xs"></i>
            Reset
        </button>

    </div>
</div>
