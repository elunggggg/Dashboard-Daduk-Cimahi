<x-layouts.public title="Demografi">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Demografi</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Data Demografi</h1>
            <p class="text-white/70 text-sm mt-1">
                Distribusi penduduk berdasarkan jenis kelamin, umur, status kawin, dan disabilitas
            </p>
        </div>
    </div>

    {{-- Filter periode/wilayah = FORM GET biasa: "Terapkan" memuat ulang
         halaman dengan query string. Konten grid dirender di server
         (dashboard/_grid); chart digambar sekali saat halaman dimuat. --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <x-filter-wilayah action="{{ route('demografi.index') }}" :kecamatanList="$kecamatanList" :wilayahList="$wilayahList" :waktuList="$waktuList"
            :kecamatan="$kecamatan" :wilayahId="$wilayahId" :waktuId="$waktuId" />

        <div>{!! $kontenHtml !!}</div>

    </div>

    <x-slot:scripts>
        @include('dashboard._skrip')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.gambarSemuaChartDaduk) window.gambarSemuaChartDaduk(@js($charts));
            });
        </script>
    </x-slot:scripts>

</x-layouts.public>
