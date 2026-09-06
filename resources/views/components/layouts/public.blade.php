@props([])
@php
    // Logo aplikasi dipakai di navbar SEMUA halaman publik, jadi diambil di sini
    // (bukan lewat prop tiap controller) supaya konsisten di manapun.
    $__logoUrl = \App\Models\PengaturanTampilan::current()->logo_url;
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'DADUK' }} — Disdukcapil Kota Cimahi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>

<body class="bg-slate-50 text-gray-800 antialiased min-h-screen flex flex-col">

    {{-- ── Navbar ──
         Latar belakang Header (Pengaturan Tampilan Petugas) SENGAJA TIDAK dipakai
         di sini lagi — sekarang khusus tampil di masthead "Dashboard Statistik
         Kependudukan" pada halaman Dashboard Publik saja (lihat
         dashboard-publik/index.blade.php). Navbar selalu polos di semua halaman. ── --}}
    <header x-data="{ open: false }"
        class="sticky top-0 z-40 transition-colors bg-brand-900 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- Brand --}}
                <a href="{{ route('dashboard.publik') }}" class="flex items-center gap-3 min-w-0">
                    @if ($__logoUrl)
                        <img src="{{ $__logoUrl }}" alt="Logo" class="w-9 h-9 rounded-xl object-cover flex-shrink-0">
                    @else
                        <div class="w-9 h-9 bg-white/10 rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-white text-xs font-black tracking-tight">DC</span>
                        </div>
                    @endif
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold leading-tight text-white">DADUK</p>
                        <p class="text-[10px] leading-tight text-white/60">Disdukcapil Kota Cimahi</p>
                    </div>
                </a>

                {{-- Desktop nav --}}
                @php
                    $navItems = [
                        ['route' => 'dashboard.publik', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                        ['route' => 'peta.index', 'label' => 'Peta', 'icon' => 'bi-geo-alt'],
                        ['route' => 'demografi.index', 'label' => 'Demografi', 'icon' => 'bi-people'],
                        ['route' => 'sosial.index', 'label' => 'Sosial', 'icon' => 'bi-heart-pulse'],
                        ['route' => 'mobilitas.index', 'label' => 'Mobilitas', 'icon' => 'bi-arrow-left-right'],
                        ['route' => 'metadata.index', 'label' => 'Metadata', 'icon' => 'bi-info-circle'],
                    ];
                @endphp
                <nav class="hidden lg:flex items-center gap-0.5">
                    @foreach ($navItems as $item)
                        <a href="{{ route($item['route']) }}"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                  {{ request()->routeIs($item['route'])
                                      ? 'bg-white/15 text-white'
                                      : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                            <i class="{{ $item['icon'] }} text-xs"></i>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                {{-- Right side --}}
                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-white text-brand-900 text-xs font-semibold rounded-lg hover:bg-white/90 transition-colors">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-white text-brand-900 text-xs font-semibold rounded-lg hover:bg-white/90 transition-colors">
                            <i class="bi bi-box-arrow-in-right"></i>
                            Masuk
                        </a>
                    @endauth

                    <button @click="open = !open"
                        class="lg:hidden p-2 rounded-lg transition-colors text-white/80 hover:bg-white/10">
                        <i class="bi text-lg" :class="open ? 'bi-x' : 'bi-list'"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile nav --}}
        <div x-show="open" x-cloak class="lg:hidden border-t border-white/10 bg-brand-900">
            <div class="px-4 py-3 space-y-1">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" @click="open = false"
                        class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs($item['route']) ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10' }}">
                        <i class="{{ $item['icon'] }}"></i>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    {{-- ── Content ── --}}
    <main class="flex-1">
        {{ $slot }}
    </main>

    {{-- ── Footer ── --}}
    <footer class="bg-brand-900 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-white/60">
                <p>Dashboard Data Agregat Penduduk Kota Cimahi — Disdukcapil Kota Cimahi © {{ date('Y') }}</p>
                <div class="flex items-center gap-4">
                    <p>Seluruh data bersifat <strong class="text-white/80">agregat/rekap</strong> — bukan data personal.</p>
                    <a href="{{ route('metadata.index') }}" class="text-white/80 hover:text-white font-medium underline-offset-2 hover:underline">
                        Metadata
                    </a>
                </div>
            </div>
        </div>
    </footer>

    {{ $scripts ?? '' }}
</body>

</html>
