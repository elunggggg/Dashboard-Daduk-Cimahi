<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — DADUK Disdukcapil Cimahi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Dijalankan sebelum body dirender agar sidebar tidak "berkedip" dari lebar
         penuh ke ciut saat halaman dimuat. --}}
    <script>
        (function() {
            try {
                if (localStorage.getItem('daduk.sidebar') === '1') {
                    document.documentElement.classList.add('is-collapsed');
                }
            } catch (e) {
                /* localStorage diblokir — abaikan, sidebar tampil penuh */
            }
        })();
    </script>
    {{ $head ?? '' }}
</head>

<body class="h-full bg-slate-50 antialiased" x-data="{
    sidebarOpen: false,
    collapsed: document.documentElement.classList.contains('is-collapsed'),
    toggleCollapse() {
        this.collapsed = !this.collapsed;
        document.documentElement.classList.toggle('is-collapsed', this.collapsed);
        try { localStorage.setItem('daduk.sidebar', this.collapsed ? '1' : '0'); } catch (e) {}
    },
}">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden">
    </div>

    {{-- Sidebar --}}
    <aside
        class="sidebar-shell fixed inset-y-0 left-0 z-50 flex flex-col bg-brand-900
               transition-transform duration-300 ease-in-out lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        {{-- Brand --}}
        @php $__logoUrl = \App\Models\PengaturanTampilan::current()->logo_url; @endphp
        <div class="sidebar-brand flex items-center gap-3 h-16 px-5 border-b border-white/10 flex-shrink-0">
            @if ($__logoUrl)
                <img src="{{ $__logoUrl }}" alt="Logo" class="w-9 h-9 rounded-xl object-cover flex-shrink-0">
            @else
                <div class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-xs font-black">DC</span>
                </div>
            @endif
            <div class="sidebar-label min-w-0">
                <p class="text-white text-sm font-bold leading-tight truncate">DADUK</p>
                <p class="text-slate-400 text-[10px] leading-tight truncate">Disdukcapil Kota Cimahi</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav
            class="flex-1 overflow-y-auto px-3 py-4 space-y-1 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-slate-700">

            {{-- Dashboard Publik --}}
            <a href="{{ route('dashboard') }}" title="Dashboard"
                class="{{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'sidebar-link-inactive' }} sidebar-link">
                <i class="bi bi-speedometer2 text-base w-5 flex-shrink-0 text-center"></i>
                <span class="sidebar-label text-sm">Dashboard</span>
            </a>

            {{-- Halaman Petugas only --}}
            @if (auth()->user()?->isPetugas())
                <p class="sidebar-label mt-5 mb-2 px-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    Halaman Petugas</p>
                @php
                    $petugasItems = [
                        ['route' => 'petugas.import.index', 'label' => 'Import Data', 'icon' => 'bi-upload'],
                        ['route' => 'petugas.wilayah.index', 'label' => 'Kelola Wilayah', 'icon' => 'bi-geo-alt'],
                        ['route' => 'petugas.pengguna.index', 'label' => 'Kelola Pengguna', 'icon' => 'bi-person-gear'],
                        ['route' => 'petugas.metadata.index', 'label' => 'Kelola Metadata', 'icon' => 'bi-book'],
                        ['route' => 'petugas.indikator.index', 'label' => 'Kelola Indikator', 'icon' => 'bi-list-check'],
                        ['route' => 'petugas.konfigurasi-import.index', 'label' => 'Konfigurasi Import', 'icon' => 'bi-diagram-3'],
                        ['route' => 'petugas.pengaturan.edit', 'label' => 'Tampilan Dash', 'icon' => 'bi-image'],
                        ['route' => 'petugas.audit.index', 'label' => 'Audit Log', 'icon' => 'bi-journal-text'],
                        ['route' => 'petugas.backup.index', 'label' => 'Backup Database', 'icon' => 'bi-database-check'],
                    ];
                @endphp
                @foreach ($petugasItems as $item)
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                        class="{{ request()->routeIs($item['route']) ? 'sidebar-link-active' : 'sidebar-link-inactive' }} sidebar-link">
                        <i class="bi {{ $item['icon'] }} text-base w-5 flex-shrink-0 text-center"></i>
                        <span class="sidebar-label text-sm">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endif
        </nav>

        {{-- Identitas pengguna. Tombol Keluar SENGAJA tidak ada di sini — satu-satunya
             pintu keluar ada di menu profil pada top bar, supaya tidak ada dua tombol
             logout yang harus dijaga tetap sama. --}}
        <div class="flex-shrink-0 border-t border-white/10 px-3 py-4">
            <div class="sidebar-brand flex items-center gap-3 px-2">
                <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-white text-xs font-bold">{{ substr(auth()->user()?->name ?? 'U', 0, 1) }}</span>
                </div>
                <div class="sidebar-label min-w-0 flex-1">
                    <p class="text-white text-xs font-semibold truncate">{{ auth()->user()?->name }}</p>
                    <p class="text-slate-400 text-[10px] truncate capitalize">{{ auth()->user()?->role }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main wrapper --}}
    <div class="sidebar-main flex flex-col min-h-full">

        {{-- Top bar --}}
        <header
            class="sticky top-0 z-30 h-16 flex items-center gap-4 bg-white/95 backdrop-blur-sm
                       border-b border-gray-200 shadow-sm px-4 sm:px-6">
            {{-- Toggle drawer (mobile) --}}
            <button @click="sidebarOpen = !sidebarOpen" type="button" aria-label="Buka menu"
                class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                <i class="bi bi-list text-xl"></i>
            </button>

            {{-- Toggle ciut/lebar (desktop) --}}
            <button @click="toggleCollapse()" type="button"
                :aria-label="collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar'"
                :title="collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar'"
                class="hidden lg:inline-flex p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                <i class="bi text-xl" :class="collapsed ? 'bi-chevron-double-right' : 'bi-chevron-double-left'"></i>
            </button>

            {{-- Page title --}}
            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-bold text-gray-900 truncate">{{ $title ?? 'Dashboard' }}</h1>
                @isset($breadcrumb)
                    <p class="text-xs text-gray-400">{{ $breadcrumb }}</p>
                @endisset
            </div>

            {{-- Period chip --}}
            @php $latestW = \App\Models\DimWaktu::orderBy('tahun','desc')->orderBy('semester','desc')->first(); @endphp
            @if ($latestW)
                <span
                    class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-3 py-1">
                    <i class="bi bi-calendar2-check text-brand-600"></i>
                    Data: {{ $latestW->label }}
                </span>
            @endif

            {{-- Ke Dashboard Publik (Dashboard Data Agregat Penduduk) --}}
            <a href="{{ route('dashboard.publik') }}" target="_blank"
                title="Buka Dashboard Publik (Dashboard Data Agregat Penduduk)"
                class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-full px-3 py-1.5 transition-colors">
                <i class="bi bi-box-arrow-up-right"></i>
                Dashboard Publik
            </a>

            {{-- Profile --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-brand-700 flex items-center justify-center">
                        <span
                            class="text-white text-xs font-bold">{{ substr(auth()->user()?->name ?? 'U', 0, 1) }}</span>
                    </div>
                    <i class="bi bi-chevron-down text-xs hidden sm:block" :class="open ? 'rotate-180' : ''"
                        style="transition:transform .2s"></i>
                </button>

                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white rounded-xl border border-gray-200 shadow-lg py-1 z-50">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-xs font-semibold text-gray-900">{{ auth()->user()?->name }}</p>
                        <p class="text-[10px] text-gray-400">Petugas</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="px-2 py-1">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <i class="bi bi-box-arrow-left"></i>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @if (session('success'))
                <div class="alert-success mb-4 flex items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert-error mb-4 flex items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    {{ session('error') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>

    {{ $scripts ?? '' }}
</body>

</html>
