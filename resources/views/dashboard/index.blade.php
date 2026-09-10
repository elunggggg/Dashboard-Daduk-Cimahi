<x-layouts.app title="Dashboard" breadcrumb="Halaman Petugas">

    <div class="space-y-5">

        {{-- ── Sambutan ── --}}
        <div class="rounded-2xl bg-gradient-to-r from-brand-800 to-brand-600 text-white p-5 sm:p-6">
            <p class="text-brand-200 text-xs font-medium mb-1">Selamat datang kembali,</p>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">{{ auth()->user()?->name }}</h1>
            <p class="text-brand-100 text-sm mt-1">Pilih fitur yang ingin dibuka di bawah ini.</p>
        </div>

        {{-- ── Ringkasan cepat ── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $stats = [
                    ['label' => 'Wilayah Terdata', 'value' => number_format($stat['wilayah'], 0, ',', '.'), 'icon' => 'bi-geo-alt-fill', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
                    ['label' => 'Baris Data Agregat', 'value' => number_format($stat['baris_data'], 0, ',', '.'), 'icon' => 'bi-database-fill', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                    ['label' => 'Periode Tersimpan', 'value' => number_format($stat['periode'], 0, ',', '.'), 'icon' => 'bi-calendar2-week-fill', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                    ['label' => 'Akun Petugas', 'value' => number_format($stat['pengguna'], 0, ',', '.'), 'icon' => 'bi-person-fill', 'bg' => 'bg-violet-100', 'text' => 'text-violet-700'],
                ];
            @endphp
            @foreach ($stats as $s)
                <div class="kpi-card">
                    <div class="kpi-icon {{ $s['bg'] }} {{ $s['text'] }}"><i class="bi {{ $s['icon'] }} text-xl"></i></div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">{{ $s['label'] }}</p>
                        <p class="text-xl font-extrabold text-gray-900 tracking-tight">{{ $s['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($stat['import_terakhir'])
            <p class="text-xs text-gray-400 flex items-center gap-1.5">
                <i class="bi bi-clock-history"></i>
                Unggah data terakhir: {{ $stat['import_terakhir']->translatedFormat('d M Y H:i') }}
            </p>
        @endif

        {{-- ── Menu fitur ──
             `$warnaKelas` WAJIB ditulis literal (bukan string dibangun dinamis
             "bg-{$warna}-50") supaya class-nya kedeteksi content scanner Tailwind
             saat build — kelas yang dirakit dari variabel PHP murni tidak akan
             pernah muncul di CSS hasil build. ── --}}
        @php
            $warnaKelas = [
                'blue'    => ['bg' => 'bg-blue-50',    'text' => 'text-blue-700',    'hover' => 'hover:border-blue-300'],
                'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'hover' => 'hover:border-emerald-300'],
                'violet'  => ['bg' => 'bg-violet-50',  'text' => 'text-violet-700',  'hover' => 'hover:border-violet-300'],
                'amber'   => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'hover' => 'hover:border-amber-300'],
                'pink'    => ['bg' => 'bg-pink-50',    'text' => 'text-pink-700',    'hover' => 'hover:border-pink-300'],
                'slate'   => ['bg' => 'bg-slate-100',  'text' => 'text-slate-700',   'hover' => 'hover:border-slate-300'],
                'teal'    => ['bg' => 'bg-teal-50',    'text' => 'text-teal-700',    'hover' => 'hover:border-teal-300'],
                'indigo'  => ['bg' => 'bg-indigo-50',  'text' => 'text-indigo-700',  'hover' => 'hover:border-indigo-300'],
                'rose'    => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'hover' => 'hover:border-rose-300'],
            ];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($menu as $item)
                @php $w = $warnaKelas[$item['warna']] ?? $warnaKelas['blue']; @endphp
                <a href="{{ route($item['route']) }}"
                    class="card p-5 flex items-start gap-4 hover:shadow-md transition-all {{ $w['hover'] }}">
                    <div class="w-11 h-11 rounded-xl {{ $w['bg'] }} {{ $w['text'] }} flex items-center justify-center flex-shrink-0">
                        <i class="bi {{ $item['icon'] }} text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900">{{ $item['label'] }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $item['deskripsi'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</x-layouts.app>
