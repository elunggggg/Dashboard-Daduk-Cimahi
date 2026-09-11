{{-- Bagian-bagian Halaman Demografi. Dirender DI DALAM .seksi-grid (flex-wrap)
     oleh dashboard/_grid.blade.php — bersama bagian Sosial — supaya bagian bisa
     dipindah antar halaman, disembunyikan, atau diperkecil, dan sisanya
     mengalir otomatis. Tiap <x-seksi> hanya benar-benar tampil kalau halaman
     DB-nya == halaman yang sedang dibuka (lihat komponen <x-seksi>).

     Redraw Chart.js ditangani gambarSemuaChart() di dashboard/_skrip, dibaca
     dari payload `charts` — BUKAN dari <script> di partial ini (konten hasil
     fetch di-inject lewat x-html yang tidak menjalankan <script>). --}}

@if (($halamanAktif ?? 'demografi') === 'demografi' && $totalPenduduk === 0)
    <div class="seksi-item seksi-w-penuh">
        <div class="alert-info flex items-center gap-2">
            <i class="bi bi-info-circle"></i>
            Tidak ada data untuk filter yang dipilih. Coba pilih periode atau wilayah lain.
        </div>
    </div>
@endif

{{-- KPI ringkas Demografi --}}
<x-seksi halaman="demografi" kunci="kpi_demografi" judul="KPI Ringkas Demografi (Total / L / P / Rasio / Kepadatan)" lebar="penuh" :urutan="0">
    @php $kpiItems = [['label' => 'Total Penduduk', 'value' => number_format($totalPenduduk, 0, ',', '.'), 'suffix' => 'jiwa', 'icon' => 'bi-people-fill', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700'], ['label' => 'Laki-laki', 'value' => number_format($laki, 0, ',', '.'), 'suffix' => 'jiwa', 'icon' => 'bi-gender-male', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'], ['label' => 'Perempuan', 'value' => number_format($perempuan, 0, ',', '.'), 'suffix' => 'jiwa', 'icon' => 'bi-gender-female', 'bg' => 'bg-pink-100', 'text' => 'text-pink-700'], ['label' => 'Rasio LK/PR', 'value' => $rasio, 'suffix' => 'per 100', 'icon' => 'bi-bar-chart-line', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'], ['label' => 'Kepadatan Penduduk', 'value' => number_format($kepadatan, 0, ',', '.'), 'suffix' => 'jiwa/km²', 'icon' => 'bi-grid-3x3-gap-fill', 'bg' => 'bg-teal-100', 'text' => 'text-teal-700']]; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach ($kpiItems as $kpi)
            <div class="kpi-card">
                <div class="kpi-icon {{ $kpi['bg'] }} {{ $kpi['text'] }}"><i class="bi {{ $kpi['icon'] }} text-xl"></i></div>
                <div>
                    <p class="text-xs font-medium text-gray-500">{{ $kpi['label'] }}</p>
                    <p class="text-xl font-extrabold text-gray-900 tracking-tight">{{ $kpi['value'] }}</p>
                    <p class="text-xs text-gray-400">{{ $kpi['suffix'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="statistik_ringkas" judul="Umur Median · Laju Pertumbuhan · WNA" lebar="penuh" :urutan="10">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="jenis_kelamin" judul="Jenis Kelamin" lebar="sepertiga" :urutan="20">
    <div class="section-card h-full">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="anak" judul="Jumlah Anak (0-14 Tahun)" lebar="sepertiga" :urutan="30">
    <div class="section-card h-full">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="lansia" judul="Jumlah Penduduk Lansia (65+ Tahun)" lebar="sepertiga" :urutan="40">
    <div class="section-card h-full">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="piramida" judul="Piramida Penduduk" lebar="penuh" :urutan="50">
    <div class="section-card">
        <div class="flex items-center justify-between mb-3">
            <h2 class="section-title mb-0">Piramida Penduduk</h2>
            <span class="text-xs text-gray-400">Kelompok umur × jenis kelamin — Periode: {{ $selectedWaktu?->label ?? '-' }}</span>
        </div>
        <div class="h-[500px]"><canvas id="chart-piramida"></canvas></div>
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="status_perkawinan" judul="Status Perkawinan" lebar="separuh" :urutan="60">
    <div class="section-card h-full">
        <h2 class="section-title">Status Perkawinan</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Penduduk usia 15 tahun ke atas</p>
        <div class="h-[300px]"><canvas id="chart-marital"></canvas></div>
        <x-rincian-indikator :data="$maritalData" :laki="$maritalLakiData" :perempuan="$maritalPerempuanData" :total="$maritalData->sum()" chart-id="chart-marital" />
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="disabilitas" judul="Disabilitas (menurut jenis keterbatasan)" lebar="separuh" :urutan="70">
    <div class="section-card h-full" id="section-disabilitas-donut">
        <h2 class="section-title">Disabilitas</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Berdasarkan jenis keterbatasan</p>
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
        <x-rincian-indikator :data="$disabilData" :laki="$disabilLakiData" :perempuan="$disabilPerempuanData" :total="$totalDisabilitas" chart-id="chart-disab" />
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="kelompok_umur" judul="Distribusi Kelompok Umur" lebar="penuh" :urutan="80">
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
        <x-rincian-indikator :data="$ageData" :laki="$ageLakiData" :perempuan="$agePerempuanData" :total="$totalPenduduk" />
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="umur_tunggal" judul="Umur Tunggal (0-99 Tahun)" lebar="penuh" :urutan="90">
    <div class="card">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Umur Tunggal (0-99 Tahun)</h2>
            <p class="text-xs text-gray-400">Jumlah penduduk per umur tunggal (laki-laki &amp; perempuan). Cari jumlah pada umur tertentu (mis. tepat 17 tahun), atau isi <strong>Rentang angka</strong> untuk menjumlahkan satu kelompok umur, mis. 17&ndash;40 tahun</p>
        </div>
        <div class="p-4">
            <x-rincian-indikator :data="$umurTunggalData" :laki="$umurTunggalLakiData" :perempuan="$umurTunggalPerempuanData" :total="$totalPenduduk" :range="true" scroll="sedang" />
        </div>
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="kelahiran" judul="Kelahiran (CBR / GFR)" lebar="penuh" :urutan="100">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="asfr" judul="Angka Kelahiran Menurut Kelompok Umur (ASFR & TFR)" lebar="penuh" :urutan="110">
    <div class="card">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Angka Kelahiran Menurut Kelompok Umur (ASFR)</h2>
            <p class="text-xs text-gray-400">Se-Kota — data ini tidak mengikuti filter kelurahan/kecamatan</p>
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
</x-seksi>

<x-seksi halaman="demografi" kunci="perkawinan_kecamatan" judul="Status Perkawinan per Kecamatan" lebar="penuh" :urutan="120">
    <div class="card">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Status Perkawinan per Kecamatan</h2>
            <p class="text-xs text-gray-400">Total seluruh kelompok umur — rincian per kelompok umur tersedia di Unduh PDF Buku Profil</p>
        </div>
        <div class="p-4 overflow-x-auto">
            @if ($perkawinanKuData->isEmpty())
                <p class="text-xs text-gray-400">Data belum tersedia untuk periode ini.</p>
            @else
                <table class="w-full text-xs [&_th]:border-r [&_td]:border-r [&_th]:border-gray-200 [&_td]:border-gray-100 [&_th:last-child]:border-r-0 [&_td:last-child]:border-r-0 [&_th]:px-2 [&_td]:px-2 [&_th]:border-b [&_th]:border-b-gray-200">
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
                    <span>Angka pada tabel ini sama antara Semester I dan II 2025 di berkas sumber — kemungkinan Disdukcapil belum memperbarui tabel ini antar-semester, ditampilkan apa adanya.</span>
                </p>
            @endif
        </div>
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="disabilitas_kecamatan" judul="Disabilitas per Kecamatan" lebar="separuh" :urutan="130">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Disabilitas per Kecamatan</h2>
            <p class="text-xs text-gray-400">Total lintas kelompok umur — rincian usia ada di Unduh PDF</p>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-xs [&_th]:border-r [&_td]:border-r [&_th]:border-gray-200 [&_td]:border-gray-100 [&_th:last-child]:border-r-0 [&_td:last-child]:border-r-0 [&_th]:px-2 [&_td]:px-2 [&_th]:border-b [&_th]:border-b-gray-200">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="golongan_darah_kecamatan" judul="Golongan Darah per Kecamatan" lebar="separuh" :urutan="140">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Golongan Darah per Kecamatan</h2>
            <p class="text-xs text-gray-400">Total lintas kelompok umur — banyak penduduk belum tercatat golongan darahnya</p>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-xs [&_th]:border-r [&_td]:border-r [&_th]:border-gray-200 [&_td]:border-gray-100 [&_th:last-child]:border-r-0 [&_td:last-child]:border-r-0 [&_th]:px-2 [&_td]:px-2 [&_th]:border-b [&_th]:border-b-gray-200">
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
</x-seksi>

<x-seksi halaman="demografi" kunci="disabilitas_pekerjaan" judul="Disabilitas Menurut Pekerjaan" lebar="separuh" :urutan="150">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Disabilitas Menurut Pekerjaan</h2>
            <p class="text-xs text-gray-400">Se-Kota — hanya jenis pekerjaan yang ada penyandangnya</p>
        </div>
        <div class="p-4">
            <x-rincian-indikator :data="$disabilitasPekerjaanData" :total="$disabilitasPekerjaanData->sum()" />
        </div>
    </div>
</x-seksi>

<x-seksi halaman="demografi" kunci="disabilitas_usia_sekolah" judul="Disabilitas Usia Sekolah per Kecamatan" lebar="separuh" :urutan="160">
    <div class="card h-full">
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
            <p class="text-[11px] text-gray-400 mt-3">Rincian per jenis disabilitas & kelompok umur ada di Unduh PDF Buku Profil.</p>
        </div>
    </div>
</x-seksi>
