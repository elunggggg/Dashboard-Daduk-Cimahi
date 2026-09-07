<x-layouts.public title="Metadata Indikator">

    <div class="bg-gradient-to-r from-brand-900 to-brand-700 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-gray-300 hover:text-white text-sm">Dashboard Publik</a>
                <span class="text-gray-400">/</span>
                <span class="text-white text-sm font-medium">Metadata</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Kamus Indikator</h1>
            <p class="text-gray-300 text-sm mt-1">
                Definisi, sumber, dan metodologi setiap indikator data kependudukan
            </p>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

        @foreach($byModul as $modul => $indikators)

            <section>
                @php
                    $modulColors = [
                        'Demografi' => ['bg'=>'bg-purple-100','text'=>'text-purple-700','border'=>'border-purple-300','icon'=>'bi-people-fill'],
                        'Sosial'    => ['bg'=>'bg-green-100', 'text'=>'text-green-700', 'border'=>'border-green-300', 'icon'=>'bi-person-check-fill'],
                        'Mobilitas' => ['bg'=>'bg-orange-100','text'=>'text-orange-700','border'=>'border-orange-300','icon'=>'bi-arrow-left-right'],
                    ];
                    $mc = $modulColors[$modul] ?? ['bg'=>'bg-gray-100','text'=>'text-gray-700','border'=>'border-gray-300','icon'=>'bi-info-circle'];
                @endphp

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-full {{ $mc['bg'] }} {{ $mc['text'] }} flex items-center justify-center">
                        <i class="bi {{ $mc['icon'] }}"></i>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900">Modul {{ $modul }}</h2>
                    <span class="badge">{{ $indikators->count() }} indikator</span>
                </div>

                <div class="space-y-3">
                    @foreach($indikators as $kode => $meta)
                        <div class="card border-l-4 {{ $mc['border'] }}">
                            <div class="p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-900">{{ $meta['nama'] }}</h3>
                                        <code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono">{{ $meta['kode'] }}</code>
                                    </div>
                                    <div class="flex gap-2 flex-wrap">
                                        <span class="badge-blue">{{ $meta['satuan'] }}</span>
                                        <span class="badge-gray">{{ $meta['periode'] }}</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $meta['definisi'] }}</p>
                                <div class="mt-2 flex items-center gap-1 text-xs text-gray-400">
                                    <i class="bi bi-building"></i>
                                    <span>Sumber: {{ $meta['sumber'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

        @endforeach

        {{-- Catatan metodologi --}}
        <div class="alert-info">
            <div class="flex gap-3">
                <i class="bi bi-shield-check text-blue-500 mt-0.5 flex-shrink-0"></i>
                <div class="text-sm text-blue-800 space-y-1">
                    <p class="font-semibold">Catatan Metodologi</p>
                    <ul class="list-disc list-inside space-y-0.5 text-blue-700">
                        <li>Seluruh data bersifat <strong>agregat</strong> — tidak ada informasi per individu/NIK.</li>
                        <li>Periode pelaporan: per semester (S1 = Januari–Juni, S2 = Juli–Desember).</li>
                        <li>Wilayah administratif mengacu pada pembagian kecamatan dan kelurahan di Kota Cimahi.</li>
                        <li>Kode wilayah mengikuti standar Kemendagri (placeholder — perlu verifikasi).</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

</x-layouts.public>
