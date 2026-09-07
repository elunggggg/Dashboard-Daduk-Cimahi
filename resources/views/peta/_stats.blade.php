{{-- Panel statistik kanan halaman Peta — diekstrak jadi partial supaya bisa
     dirender ULANG DI SERVER saat filter berubah lewat fetch (Phase 5, tanpa
     reload), dipakai baik oleh kunjungan HTML pertama (peta/index.blade.php)
     maupun endpoint JSON (PetaController, kunci 'stats_html'). --}}
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

    {{-- Jalan keluar dari mode tunggal — pakai data-fokus-kecamatan (dibaca
         Alpine di peta/index.blade.php) supaya tidak perlu reload. --}}
    <button type="button" class="btn-secondary w-full justify-center"
            data-fokus-kecamatan="{{ $kel->nama_kecamatan }}"
            onclick="window._petaGantiFilter(this.dataset.fokusKecamatan, '')">
        <i class="bi bi-arrow-left"></i>
        Lihat seluruh Kec. {{ $kel->nama_kecamatan }}
    </button>

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
                <button type="button"
                       data-kelurahan-id="{{ $kel->id }}"
                       onclick="window._petaGantiFilter('', this.dataset.kelurahanId)"
                       class="flex w-full items-center justify-between text-xs rounded px-1 -mx-1 py-0.5 text-left
                              {{ $kelurahanId === $kel->id ? 'bg-brand-50 text-brand-800 font-semibold' : 'hover:bg-gray-50' }}">
                    <span class="truncate {{ $kelurahanId === $kel->id ? '' : 'text-gray-600' }}">{{ $kel->nama_kelurahan }}</span>
                    <span class="font-medium flex-shrink-0 ml-2 {{ $kelurahanId === $kel->id ? '' : 'text-gray-800' }}">
                        {{ number_format($kel->total_penduduk ?? 0, 0, ',', '.') }}
                    </span>
                </button>
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
