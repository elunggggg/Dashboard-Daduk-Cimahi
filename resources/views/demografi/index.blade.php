<x-layouts.public title="Demografi">

    {{-- Hero --}}
    <div class="bg-brand-900 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard
                    Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Demografi</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Data Demografi</h1>
            <p class="text-white/70 text-sm mt-1">
                Distribusi penduduk berdasarkan jenis kelamin, umur, status kawin, dan disabilitas
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Filter --}}
        <x-filter-wilayah action="{{ route('demografi.index') }}" :kecamatanList="$kecamatanList" :wilayahList="$wilayahList" :waktuList="$waktuList"
            :kecamatan="$kecamatan" :wilayahId="$wilayahId" :waktuId="$waktuId" />

        @if ($totalPenduduk === 0)
            <div class="alert-info flex items-center gap-2">
                <i class="bi bi-info-circle"></i>
                Tidak ada data untuk filter yang dipilih. Coba pilih periode atau wilayah lain.
            </div>
        @endif

        {{-- KPI strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            @php $kpiItems = [['label' => 'Total Penduduk', 'value' => number_format($totalPenduduk, 0, ',', '.'), 'suffix' => 'jiwa', 'icon' => 'bi-people-fill', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700'], ['label' => 'Laki-laki', 'value' => number_format($laki, 0, ',', '.'), 'suffix' => 'jiwa', 'icon' => 'bi-gender-male', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'], ['label' => 'Perempuan', 'value' => number_format($perempuan, 0, ',', '.'), 'suffix' => 'jiwa', 'icon' => 'bi-gender-female', 'bg' => 'bg-pink-100', 'text' => 'text-pink-700'], ['label' => 'Rasio LK/PR', 'value' => $rasio, 'suffix' => 'per 100', 'icon' => 'bi-bar-chart-line', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'], ['label' => 'Kepadatan Penduduk', 'value' => number_format($kepadatan, 0, ',', '.'), 'suffix' => 'jiwa/km²', 'icon' => 'bi-grid-3x3-gap-fill', 'bg' => 'bg-teal-100', 'text' => 'text-teal-700']]; @endphp
            @foreach ($kpiItems as $kpi)
                <div class="kpi-card">
                    <div class="kpi-icon {{ $kpi['bg'] }} {{ $kpi['text'] }}"><i
                            class="bi {{ $kpi['icon'] }} text-xl"></i></div>
                    <div>
                        <p class="text-xs font-medium text-gray-500">{{ $kpi['label'] }}</p>
                        <p class="text-xl font-extrabold text-gray-900 tracking-tight">{{ $kpi['value'] }}</p>
                        <p class="text-xs text-gray-400">{{ $kpi['suffix'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Statistik tambahan: Umur Median, LPP, WNA --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="card p-3 text-center">
                <p class="text-lg font-extrabold text-gray-900">{{ number_format($umurMedian, 1, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500">Umur Median (tahun)</p>
            </div>
            <div class="card p-3 text-center">
                <p class="text-lg font-extrabold text-gray-900">{{ number_format($lpp, 2, ',', '.') }}%</p>
                <p class="text-[11px] text-gray-500">Laju Pertumbuhan Penduduk</p>
            </div>
            <div class="card p-3 text-center">
                <p class="text-lg font-extrabold text-gray-900">{{ number_format($totalWna, 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500">Penduduk WNA (jiwa)</p>
            </div>
        </div>

        {{-- KPI donut: Jenis Kelamin, Jumlah Anak (0-14), Jumlah Penduduk Lansia (65+) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            {{-- Jenis Kelamin --}}
            <div class="section-card">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="section-title mb-0">Jenis Kelamin</h2>
                    <span class="text-xs text-gray-400">{{ $selectedWaktu?->label ?? '-' }}</span>
                </div>
                <div class="h-[250px]"><canvas id="chart-gender"></canvas></div>
                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                    @foreach ($genderData as $label => $jumlah)
                        @php $pct = $totalPenduduk > 0 ? round($jumlah / $totalPenduduk * 100, 2) : 0; @endphp
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-gray-600">
                                <span class="h-2 w-2 rounded-full" style="background:{{ $label === 'Laki-laki' ? '#3B82F6' : '#EC4899' }}"></span>
                                {{ $label }}
                            </span>
                            <strong class="text-gray-900">{{ number_format($jumlah, 0, ',', '.') }}
                                <span class="text-gray-400 font-normal">({{ number_format($pct, 2, ',', '.') }}%)</span></strong>
                        </div>
                    @endforeach
                </div>
                <x-rincian-indikator :data="$genderData" :total="$totalPenduduk" chart-id="chart-gender" />
            </div>

            {{-- Jumlah Anak (0-14 tahun) --}}
            <div class="section-card">
                <h2 class="section-title">Jumlah Anak (0-14 Tahun)</h2>
                <div class="h-[250px]"><canvas id="chart-anak"></canvas></div>
                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                    @foreach ($anakData as $label => $jumlah)
                        @php $pct = $anakData->sum() > 0 ? round($jumlah / $anakData->sum() * 100, 2) : 0; @endphp
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-gray-600">
                                <span class="h-2 w-2 rounded-full" style="background:{{ $label === 'Laki-laki' ? '#3B82F6' : '#EC4899' }}"></span>
                                {{ $label }}
                            </span>
                            <strong class="text-gray-900">{{ number_format($jumlah, 0, ',', '.') }}
                                <span class="text-gray-400 font-normal">({{ number_format($pct, 2, ',', '.') }}%)</span></strong>
                        </div>
                    @endforeach
                </div>
                <x-rincian-indikator :data="$anakData" :total="$anakData->sum()" chart-id="chart-anak" />
            </div>

            {{-- Jumlah Penduduk Lansia (65+ tahun) --}}
            <div class="section-card">
                <h2 class="section-title">Jumlah Penduduk Lansia (65+ Tahun)</h2>
                <div class="h-[250px]"><canvas id="chart-lansia"></canvas></div>
                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5">
                    @foreach ($lansiaData as $label => $jumlah)
                        @php $pct = $lansiaData->sum() > 0 ? round($jumlah / $lansiaData->sum() * 100, 2) : 0; @endphp
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-1.5 text-gray-600">
                                <span class="h-2 w-2 rounded-full" style="background:{{ $label === 'Laki-laki' ? '#3B82F6' : '#EC4899' }}"></span>
                                {{ $label }}
                            </span>
                            <strong class="text-gray-900">{{ number_format($jumlah, 0, ',', '.') }}
                                <span class="text-gray-400 font-normal">({{ number_format($pct, 2, ',', '.') }}%)</span></strong>
                        </div>
                    @endforeach
                </div>
                <x-rincian-indikator :data="$lansiaData" :total="$lansiaData->sum()" chart-id="chart-lansia" />
            </div>
        </div>

        {{-- Piramida Penduduk --}}
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-5">
            <div class="section-card">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="section-title mb-0">Piramida Penduduk</h2>
                    <span class="text-xs text-gray-400">Kelompok umur × jenis kelamin — Periode: {{ $selectedWaktu?->label ?? '-' }}</span>
                </div>
                <div class="h-[500px]"><canvas id="chart-piramida"></canvas></div>
            </div>
        </div>

        {{-- Status Perkawinan + Disabilitas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="section-card">
                <h2 class="section-title">Status Perkawinan</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Penduduk usia 15 tahun ke atas</p>
                <div class="h-[300px]"><canvas id="chart-marital"></canvas></div>
                <x-rincian-indikator :data="$maritalData" :total="$maritalData->sum()" chart-id="chart-marital" />
            </div>

            <div class="section-card" id="section-disabilitas-donut">
                <h2 class="section-title">Disabilitas</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Berdasarkan jenis keterbatasan</p>

                {{-- Non-disabilitas ditaruh di bilah ringkasan, bukan di dalam donut:
                     nilainya ratusan ribu sedangkan tiap jenis disabilitas hanya
                     ratusan, jadi satu slice akan membuat enam slice lain nyaris
                     tak terlihat. --}}
                <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-gray-100 mb-2">
                    <div class="bg-amber-500" style="width:{{ max($pctDisabilitas, 0.5) }}%"></div>
                </div>
                <div class="mb-3 flex flex-wrap justify-between gap-2 text-xs">
                    <span class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        Penyandang disabilitas
                        <strong class="text-gray-900">{{ number_format($totalDisabilitas, 0, ',', '.') }}</strong>
                        <span class="text-gray-400">({{ number_format($pctDisabilitas, 2, ',', '.') }}%)</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                        Non-disabilitas
                        <strong class="text-gray-900">{{ number_format($nonDisabilitas, 0, ',', '.') }}</strong>
                    </span>
                </div>

                <div class="h-[250px]"><canvas id="chart-disab"></canvas></div>
                <x-rincian-indikator :data="$disabilData" :total="$totalDisabilitas" chart-id="chart-disab" />
            </div>
        </div>

        {{-- Distribusi Kelompok Umur — ringkasan angka (grafiknya sudah jadi
             Piramida Penduduk di atas, jadi di sini tinggal statistik turunan
             + tabel rincian per kelompok umur). --}}
        <div class="section-card">
            <div class="flex items-center justify-between mb-3">
                <h2 class="section-title mb-0">Distribusi Kelompok Umur</h2>
                <span class="badge-blue">{{ $ageData->count() }} kelompok</span>
            </div>
            <p class="text-xs text-gray-400 mb-3">
                Usia produktif (15–64 tahun): <strong class="text-gray-700">{{ number_format($produktif, 0, ',', '.') }}</strong> jiwa
                @if ($totalPenduduk > 0)
                    ({{ round(($produktif / $totalPenduduk) * 100, 1) }}%)
                @endif
            </p>
            <div class="grid grid-cols-3 gap-3 mb-4 text-center">
                <div class="rounded-lg bg-gray-50 p-2.5">
                    <p class="text-[11px] text-gray-500">Usia Muda (0-14)</p>
                    <p class="text-sm font-bold text-gray-900">{{ number_format($usiaMuda, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-2.5">
                    <p class="text-[11px] text-gray-500">Usia Tua (65+)</p>
                    <p class="text-sm font-bold text-gray-900">{{ number_format($usiaTua, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-2.5">
                    <p class="text-[11px] text-gray-500">Rasio Ketergantungan</p>
                    <p class="text-sm font-bold text-gray-900">{{ number_format($rasioKetergantungan, 1, ',', '.') }}%</p>
                </div>
            </div>
            <x-rincian-indikator :data="$ageData" :total="$totalPenduduk" />
        </div>

        {{-- Umur Tunggal (0-99 Tahun) --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Umur Tunggal (0-99 Tahun)</h2>
                <p class="text-xs text-gray-400">Cari jumlah penduduk pada umur tertentu, mis. tepat 17 tahun</p>
            </div>
            <div class="p-4">
                <x-rincian-indikator :data="$umurTunggalData" :total="$totalPenduduk" />
            </div>
        </div>

        {{-- Kelahiran (CBR/GFR) --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Kelahiran</h2>
                <p class="text-xs text-gray-400">Angka Kelahiran Kasar (CBR) & Angka Kelahiran Umum (GFR)</p>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-[11px] text-gray-500">Penduduk Usia 0 Tahun</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($usia0, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-[11px] text-gray-500">CBR</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($cbr, 2, ',', '.') }}</p>
                        <p class="text-[10px] text-gray-400">per 1.000 penduduk</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-[11px] text-gray-500">GFR</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($gfr, 2, ',', '.') }}</p>
                        <p class="text-[10px] text-gray-400">per 1.000 perempuan usia 15-49 th</p>
                    </div>
                </div>
                <p class="text-[11px] text-gray-400 flex items-start gap-1.5">
                    <i class="bi bi-info-circle-fill mt-0.5"></i>
                    <span>
                        DKB tidak mencatat "jumlah kelahiran" secara langsung — jumlah penduduk usia 0 tahun
                        dipakai sebagai proksi, sama seperti metode buku profil kependudukan resmi. Angka usia 0
                        diketahui cenderung <strong>under-registrasi</strong> (bayi yang belum sempat dilaporkan),
                        sehingga CBR/GFR pada periode yang baru berjalan bisa tampak lebih rendah dari kondisi
                        sesungguhnya.
                    </span>
                </p>
            </div>
        </div>

        {{-- ASFR & TFR --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Angka Kelahiran Menurut Kelompok Umur (ASFR)</h2>
                <p class="text-xs text-gray-400">
                    Se-Kota — data ini tidak mengikuti filter kelurahan/kecamatan
                </p>
            </div>
            <div class="p-4">
                <div class="rounded-lg bg-brand-50 p-3 mb-3 inline-block">
                    <p class="text-[11px] text-brand-700">TFR (Total Fertility Rate)</p>
                    <p class="text-lg font-bold text-brand-800">{{ number_format($tfr, 2, ',', '.') }}
                        <span class="text-[10px] font-normal text-brand-700">anak/perempuan</span>
                    </p>
                </div>
                <div class="space-y-2">
                    @foreach ($asfrData as $band => $nilai)
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-600">{{ $band }}</span>
                            <span class="font-medium text-gray-900">{{ number_format($nilai, 2, ',', '.') }} <span class="text-gray-400">/ 1.000 perempuan</span></span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Status Perkawinan per Kecamatan --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Status Perkawinan per Kecamatan</h2>
                <p class="text-xs text-gray-400">
                    Total seluruh kelompok umur — rincian per kelompok umur tersedia di Ekspor PDF Buku Profil
                </p>
            </div>
            <div class="p-4 overflow-x-auto">
                @if ($perkawinanKuData->isEmpty())
                    <p class="text-xs text-gray-400">Data belum tersedia untuk periode ini.</p>
                @else
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-medium">Kecamatan</th>
                                @foreach ($perkawinanKuJenis as $label)
                                    <th class="pb-2 font-medium text-right">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perkawinanKuData as $namaKecamatan => $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1.5 text-gray-700">{{ $namaKecamatan }}</td>
                                    @foreach ($perkawinanKuJenis as $jenis => $label)
                                        <td class="py-1.5 text-right text-gray-900">{{ number_format($row->get($jenis, 0), 0, ',', '.') }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-[11px] text-gray-400 mt-3 flex items-start gap-1.5">
                        <i class="bi bi-info-circle-fill mt-0.5"></i>
                        <span>
                            Angka pada tabel ini sama antara Semester I dan II 2025 di berkas sumber — kemungkinan
                            Disdukcapil belum memperbarui tabel ini antar-semester, ditampilkan apa adanya.
                        </span>
                    </p>
                @endif
            </div>
        </div>

        {{-- Disabilitas & Golongan Darah per Kecamatan --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Disabilitas per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Total lintas kelompok umur — rincian usia ada di Ekspor PDF</p>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-medium">Kecamatan</th>
                                @foreach (['Fisik','Netra/Buta','Rungu/Wicara','Mental/Jiwa','Fisik dan Mental','Lainnya'] as $label)
                                    <th class="pb-2 font-medium text-right">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($disabilitasKuData as $namaKecamatan => $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1.5 text-gray-700">{{ $namaKecamatan }}</td>
                                    @foreach (['Fisik','Netra/Buta','Rungu/Wicara','Mental/Jiwa','Fisik dan Mental','Lainnya'] as $label)
                                        <td class="py-1.5 text-right text-gray-900">{{ number_format($row->get($label, 0), 0, ',', '.') }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Golongan Darah per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Total lintas kelompok umur — banyak penduduk belum tercatat golongan darahnya</p>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-medium">Kecamatan</th>
                                @foreach (['A','B','AB','O','A+','A-','B+','B-','O+','O-','AB+','AB-'] as $label)
                                    <th class="pb-2 font-medium text-right">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($golDarKuData as $namaKecamatan => $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1.5 text-gray-700">{{ $namaKecamatan }}</td>
                                    @foreach (['A','B','AB','O','A+','A-','B+','B-','O+','O-','AB+','AB-'] as $label)
                                        <td class="py-1.5 text-right text-gray-900">{{ number_format($row->get($label, 0), 0, ',', '.') }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Disabilitas: Pekerjaan & Usia Sekolah --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Disabilitas Menurut Pekerjaan</h2>
                    <p class="text-xs text-gray-400">Se-Kota — hanya jenis pekerjaan yang ada penyandangnya</p>
                </div>
                <div class="p-4">
                    <x-rincian-indikator :data="$disabilitasPekerjaanData" :total="$disabilitasPekerjaanData->sum()" />
                </div>
            </div>

            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Disabilitas Usia Sekolah per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Total lintas jenis disabilitas & kelompok umur sekolah (4-18 tahun)</p>
                </div>
                <div class="p-4">
                    @foreach ($disabilitasUsklhData as $namaKecamatan => $jumlah)
                        <div class="flex justify-between text-xs py-1.5 border-b border-gray-50 last:border-0">
                            <span class="text-gray-600">{{ $namaKecamatan }}</span>
                            <span class="font-medium text-gray-900">{{ number_format($jumlah, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <p class="text-[11px] text-gray-400 mt-3">
                        Rincian per jenis disabilitas & kelompok umur ada di Ekspor PDF Buku Profil.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <x-slot:scripts>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const fmt = window.formatAngka;
                const W = window.DadukColors;
                const K = window.DadukKategori;

                function donutCenter(canvasId, labels, values, subtext) {
                    const total = values.reduce((a, b) => a + b, 0);
                    return new Chart(document.getElementById(canvasId), {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{
                                data: values,
                                backgroundColor: labels.map((l) => l === 'Laki-laki' ? W.laki : (l === 'Perempuan' ? W.perempuan : K[0])),
                                borderWidth: 0,
                                hoverOffset: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            layout: { padding: 16 },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                                centerText: { display: true, text: fmt(total), subtext },
                                datalabels: {
                                    display: true,
                                    anchor: 'end',
                                    align: 'end',
                                    offset: 6,
                                    color: '#374151',
                                    font: { size: 10, weight: '600' },
                                    formatter: (v) => fmt(v),
                                },
                            },
                        },
                    });
                }

                // ── Jenis Kelamin — donut ────────────────────────────────
                donutCenter('chart-gender', @json($genderData->keys()->values()), @json($genderData->values()), 'jiwa');

                // ── Jumlah Anak (0-14) — donut ───────────────────────────
                donutCenter('chart-anak', @json($anakData->keys()->values()), @json($anakData->values()), 'anak');

                // ── Jumlah Penduduk Lansia (65+) — donut ─────────────────
                donutCenter('chart-lansia', @json($lansiaData->keys()->values()), @json($lansiaData->values()), 'lansia');

                // ── Piramida Penduduk — butterfly horizontal bar ─────────
                const piramidaLabels = @json($ageLakiData->keys()->values());
                const piramidaLaki = @json($ageLakiData->values());
                const piramidaPerempuan = @json($agePerempuanData->values());
                new Chart(document.getElementById('chart-piramida'), {
                    type: 'bar',
                    data: {
                        labels: piramidaLabels,
                        datasets: [
                            { label: 'Laki-laki', data: piramidaLaki.map((v) => -v), backgroundColor: W.laki, borderRadius: 3, borderSkipped: false },
                            { label: 'Perempuan', data: piramidaPerempuan, backgroundColor: W.perempuan, borderRadius: 3, borderSkipped: false },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        layout: { padding: { left: 30, right: 30 } },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 11 } } },
                            tooltip: {
                                callbacks: {
                                    label: (c) => `${c.dataset.label}: ${fmt(Math.abs(c.raw))} jiwa`,
                                },
                            },
                            datalabels: {
                                display: true,
                                anchor: 'end',
                                align: 'end',
                                clamp: true,
                                color: '#374151',
                                font: { size: 9, weight: '600' },
                                formatter: (v) => fmt(Math.abs(v)),
                            },
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: { color: '#f0f0f0' },
                                ticks: {
                                    font: { size: 9 },
                                    callback: (v) => fmt(Math.abs(v)),
                                },
                            },
                            y: {
                                stacked: true,
                                grid: { display: false },
                                ticks: { font: { size: 10 } },
                            },
                        },
                    },
                });

                // ── Marital — horizontal bar ─────────────────────────────
                const maritalLabels = @json($maritalData->keys()->values());
                const maritalValues = @json($maritalData->values());
                const maritalTotal = maritalValues.reduce((a, b) => a + b, 0);
                new Chart(document.getElementById('chart-marital'), {
                    type: 'bar',
                    data: {
                        labels: maritalLabels,
                        datasets: [{
                            data: maritalValues,
                            backgroundColor: maritalLabels.map((_, i) => K[i % K.length]),
                            borderRadius: 6,
                            borderSkipped: false,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: window.tooltipPersenLabel(maritalTotal) } },
                            datalabels: {
                                display: true,
                                anchor: 'end',
                                align: 'end',
                                clamp: true,
                                color: '#374151',
                                font: { size: 9, weight: '600' },
                                formatter: (v) => fmt(v),
                            },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                            y: { grid: { display: false }, ticks: { font: { size: 10 } } },
                        },
                    },
                });

                // ── Disabilitas — donut ──────────────────────────────────
                const disabLabels = @json($disabilData->keys()->values());
                const disabValues = @json($disabilData->values());
                const disabTotal = disabValues.reduce((a, b) => a + b, 0);
                new Chart(document.getElementById('chart-disab'), {
                    type: 'doughnut',
                    data: {
                        labels: disabLabels,
                        datasets: [{
                            data: disabValues,
                            backgroundColor: disabLabels.map((_, i) => K[i % K.length]),
                            borderWidth: 0,
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        layout: { padding: 16 },
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 10, font: { size: 9 } } },
                            tooltip: { callbacks: { label: window.tooltipPersenLabel(disabTotal) } },
                            centerText: { display: true, text: fmt(disabTotal), subtext: 'jiwa' },
                            datalabels: {
                                display: true,
                                anchor: 'end',
                                align: 'end',
                                offset: 4,
                                color: '#374151',
                                font: { size: 9, weight: '600' },
                                formatter: (v) => fmt(v),
                            },
                        },
                    },
                });
            });
        </script>
    </x-slot:scripts>

</x-layouts.public>
