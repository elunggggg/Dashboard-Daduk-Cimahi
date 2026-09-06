@props([
    'action',
    'kecamatanList'  => collect(),
    'wilayahList'    => collect(),
    'waktuList'      => collect(),
    'kecamatan'      => null,
    'wilayahId'      => null,
    'waktuId'        => null,
    'showWaktu'      => true,
    'submitLabel'    => 'Tampilkan',
])

<div
    x-data="{
        selectedKecamatan: @js($kecamatan ?? ''),
        wilayahAll: @js($wilayahList->values()),
        get filteredKelurahan() {
            if (!this.selectedKecamatan) return this.wilayahAll;
            return this.wilayahAll.filter(w => w.nama_kecamatan === this.selectedKecamatan);
        },
    }"
    class="card p-4"
>
    <form method="GET" action="{{ $action }}" class="flex flex-wrap items-end gap-3">

        {{-- Kecamatan --}}
        <div class="flex-1 min-w-[180px]">
            <label class="form-label text-xs">Kecamatan</label>
            <select
                name="kecamatan"
                x-model="selectedKecamatan"
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
            <select name="wilayah_id" class="form-select text-sm">
                <option value="">Semua Kelurahan</option>
                <template x-for="w in filteredKelurahan" :key="w.id">
                    <option :value="w.id" :selected="w.id == {{ $wilayahId ?? 'null' }}"
                            x-text="w.nama_kelurahan"></option>
                </template>
            </select>
        </div>

        {{-- Periode — hanya dirender bila memang ADA yang bisa dipilih.
             Dengan 0 periode dropdown-nya kosong sama sekali, dengan 1 periode
             satu-satunya pilihannya adalah keadaan yang sudah tampil: dua-duanya
             kontrol yang tidak bisa mengubah apa pun. Saat disembunyikan form
             tidak mengirim waktu_id, dan periodeTerpilih() jatuh ke periode
             terbaru — persis periode yang sedang ditampilkan. --}}
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
                <select name="waktu_id" class="form-select text-sm">
                    @foreach($waktuList as $waktu)
                        <option value="{{ $waktu->id }}" @selected($waktuId == $waktu->id)>
                            {{ $waktu->label }}
                        </option>
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

        <button type="submit" class="btn-primary py-2">
            <i class="bi bi-funnel text-xs"></i>
            {{ $submitLabel }}
        </button>

        @if(request()->hasAny(['kecamatan','wilayah_id','waktu_id']))
            <a href="{{ $action }}" class="btn-secondary py-2">
                <i class="bi bi-x-circle text-xs"></i>
                Reset
            </a>
        @endif

    </form>
</div>
