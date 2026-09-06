@props([
    // Dipakai bila modal ini punya formnya sendiri (mis. tombol Hapus)
    'action'      => null,
    'method'      => 'POST',

    // Dipakai bila tombol harus mengirim form LAIN yang sudah ada di halaman
    // (mis. tombol Simpan pada form pratinjau yang punya banyak isian).
    // Isi dengan id form tujuan — atribut form="" milik HTML yang mengerjakannya.
    'form'        => null,

    'judul',
    'pesan'       => null,
    'tombol'      => 'Lanjutkan',
    'batal'       => 'Batal',
    'varian'      => 'bahaya',   // bahaya | peringatan | utama

    // Tampilan tombol pemicu
    'pemicu'      => null,       // teks tombol; kosongkan untuk tombol ikon saja
    'ikon'        => null,
    'kelas'       => 'btn-secondary',
    'judulPemicu' => null,       // atribut title, penting untuk tombol ikon
    'nonaktif'    => false,
])

@php
    $gaya = [
        'bahaya' => [
            'ikonLatar'  => 'bg-red-100 text-red-600',
            'ikonModal'  => 'bi-exclamation-triangle-fill',
            'tombol'     => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
        ],
        'peringatan' => [
            'ikonLatar'  => 'bg-amber-100 text-amber-600',
            'ikonModal'  => 'bi-exclamation-circle-fill',
            'tombol'     => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
        ],
        'utama' => [
            'ikonLatar'  => 'bg-brand-100 text-brand-700',
            'ikonModal'  => 'bi-question-circle-fill',
            'tombol'     => 'bg-brand-700 hover:bg-brand-800 focus:ring-brand-500',
        ],
    ][$varian] ?? [];

    // id unik supaya aria-labelledby tetap benar walau ada banyak modal di satu halaman
    $id = 'konfirmasi-'.uniqid();
@endphp

{{--
    Modal konfirmasi untuk tindakan yang sulit atau tidak bisa dibatalkan.

    Kenapa tidak memakai confirm() bawaan browser: dialog bawaan tidak bisa
    memuat rincian (jumlah baris, periode, daftar yang terdampak), padahal
    justru rincian itulah yang membuat Petugas bisa memutuskan dengan sadar —
    bukan sekadar menekan OK karena sudah terbiasa.
--}}
<div x-data="{
        buka: false,
        bukaModal() {
            this.buka = true;
            document.body.classList.add('overflow-hidden');
            {{-- Fokus dipindah ke tombol batal, bukan ke tombol aksi.
                 Kalau tombol aksi yang difokus, menekan Enter setelah modal
                 muncul akan langsung menjalankan tindakan destruktifnya. --}}
            this.$nextTick(() => this.$refs.batal?.focus());
        },
        tutup() {
            this.buka = false;
            document.body.classList.remove('overflow-hidden');
        },
     }"
     class="inline-block">

    <button type="button" @click="bukaModal()"
            class="{{ $kelas }}"
            @if($judulPemicu) title="{{ $judulPemicu }}" @endif
            @disabled($nonaktif)>
        @if($ikon)<i class="bi {{ $ikon }}"></i>@endif
        @if($pemicu){{ $pemicu }}@endif
    </button>

    {{-- Diteleport ke <body> karena banyak tombol ini berada di dalam tabel
         ber-overflow-x-auto — modal yang dirender di tempat asalnya akan
         terpotong mengikuti area gulir tabel. --}}
    <template x-teleport="body">
        <div x-show="buka" x-cloak
             @keydown.escape.window="tutup()"
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-judul">

            <div x-show="buka"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="tutup()"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-[1px]"></div>

            <div class="relative min-h-full flex items-center justify-center p-4">
                <div x-show="buka"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-2 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-2 sm:scale-95"
                     class="relative w-full max-w-lg rounded-xl bg-white shadow-xl">

                    <div class="p-6">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full {{ $gaya['ikonLatar'] }}">
                                <i class="bi {{ $gaya['ikonModal'] }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 id="{{ $id }}-judul" class="text-base font-bold text-gray-900">
                                    {{ $judul }}
                                </h3>
                                @if($pesan)
                                    <p class="mt-1 text-sm text-gray-500">{{ $pesan }}</p>
                                @endif

                                {{-- Rincian tambahan: daftar terdampak, jumlah baris, dsb. --}}
                                @if(trim($slot) !== '')
                                    <div class="mt-3 text-sm">{{ $slot }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 px-6 py-4 bg-gray-50 rounded-b-xl">
                        <button type="button" x-ref="batal" @click="tutup()" class="btn-secondary justify-center">
                            {{ $batal }}
                        </button>

                        @if($form)
                            {{-- Mengirim form lain lewat atribut form="" --}}
                            <button type="submit" form="{{ $form }}"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 text-white text-sm
                                           font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2
                                           transition-colors {{ $gaya['tombol'] }}">
                                {{ $tombol }}
                            </button>
                        @else
                            <form method="POST" action="{{ $action }}" class="contents">
                                @csrf
                                @if(strtoupper($method) !== 'POST')
                                    @method($method)
                                @endif

                                {{-- Input tersembunyi tambahan, mis. nama berkas backup --}}
                                {{ $input ?? '' }}

                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-white text-sm
                                               font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2
                                               transition-colors {{ $gaya['tombol'] }}">
                                    {{ $tombol }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
