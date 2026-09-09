<x-layouts.public title="Mobilitas">

    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Mobilitas</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Mobilitas Penduduk</h1>
            <p class="text-white/70 text-sm mt-1">
                Pergerakan penduduk masuk (datang) dan keluar (pindah) Kota Cimahi per semester
            </p>
        </div>
    </div>

    {{-- Filter tanpa reload. Konten = bagian yang halaman DB-nya "mobilitas"
         (bisa diatur / dipindah Petugas lewat menu "Bagian Dashboard"),
         dirender di dashboard/_grid; kanvas Chart.js di-redraw oleh
         gambarSemuaChart() (dashboard/_skrip). Angka per-kelurahan & tren
         mengikuti periode saja, bukan filter wilayah. --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
         x-data="dashboardGridApp({
             konten_html: @js($kontenHtml),
             charts: @js($charts),
             rute: '{{ route('mobilitas.index') }}',
         })"
         @filter-berubah.window="muat($event.detail)"
    >

        <x-filter-wilayah action="{{ route('mobilitas.index') }}" :kecamatanList="$kecamatanList" :wilayahList="$wilayahList" :waktuList="$waktuList"
            :kecamatan="$kecamatan" :wilayahId="$wilayahId" :waktuId="$waktuId" />

        <div x-show="loading" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-skeleton-card :chart="false" :rows="2" /><x-skeleton-card :chart="false" :rows="2" />
                <x-skeleton-card :chart="false" :rows="2" /><x-skeleton-card :chart="false" :rows="2" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-skeleton-card :rows="3" /><x-skeleton-card :rows="3" />
            </div>
        </div>

        <div x-show="!loading" x-cloak x-html="kontenHtml"></div>

    </div>

    <x-slot:scripts>
        @include('dashboard._skrip')
    </x-slot:scripts>

</x-layouts.public>
