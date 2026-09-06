<x-layouts.public title="Sosial">

    <div class="bg-brand-900 text-white py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('dashboard.publik') }}" class="text-white/60 hover:text-white text-sm">Dashboard Publik</a>
                <span class="text-white/40">/</span>
                <span class="text-white text-sm font-medium">Sosial</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Data Sosial</h1>
            <p class="text-white/70 text-sm mt-1">
                Pendidikan, pekerjaan, agama, dan kepemilikan dokumen kependudukan
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <x-filter-wilayah
            action="{{ route('sosial.index') }}"
            :kecamatanList="$kecamatanList"
            :wilayahList="$wilayahList"
            :waktuList="$waktuList"
            :kecamatan="$kecamatan"
            :wilayahId="$wilayahId"
            :waktuId="$waktuId"
        />

        @if($totalPenduduk === 0)
            <div class="alert-info flex items-center gap-2">
                <i class="bi bi-info-circle"></i>
                Tidak ada data untuk filter yang dipilih.
            </div>
        @endif

        {{-- Dokumen Kependudukan — progress bars --}}
        <div class="card" x-data="{ pilihDok: '' }">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Kepemilikan Dokumen Kependudukan</h2>
                    <p class="text-xs text-gray-400">Periode: {{ $selectedWaktu?->label ?? '-' }}</p>
                </div>
                @php
                    // 'dari' = penyebut yang dipakai SosialController, ditulis di
                    // layar supaya persentasenya tidak bisa disalahartikan.
                    $dokumen = [
                        ['label'=>'KTP-el sudah cetak', 'pct'=>$pctKtp,       'jumlah'=>$ktpData->get('Sudah Cetak KTP',0),           'dari'=>'wajib KTP',        'color'=>'bg-blue-500'],
                        ['label'=>'KK sudah TTE',       'pct'=>$pctKK,        'jumlah'=>$kkData->get('KK Sudah TTE',0),               'dari'=>'kepala keluarga',  'color'=>'bg-green-500'],
                        ['label'=>'KIA (anak 0–17 th)', 'pct'=>$pctKia,       'jumlah'=>$kiaData->get('Memiliki KIA',0),              'dari'=>'anak 0–17 tahun',  'color'=>'bg-orange-500'],
                        ['label'=>'Akta Kelahiran',     'pct'=>$pctAktaLahir, 'jumlah'=>$aktaLahirData->get('Memiliki Akta Lahir',0), 'dari'=>'penduduk',         'color'=>'bg-purple-500'],
                        ['label'=>'Akta Perkawinan',    'pct'=>$pctAktaKawin, 'jumlah'=>$aktaKawinData->get('Memiliki Akta Kawin',0), 'dari'=>'berstatus kawin',  'color'=>'bg-teal-500'],
                        ['label'=>'Akta Lahir (0-5 th)',  'pct'=>$pctAktaLahir05,  'jumlah'=>$aktaLahir05Data->get('Memiliki Akta Lahir 0-5 Tahun',0),   'dari'=>'anak 0-5 tahun',  'color'=>'bg-cyan-500'],
                        ['label'=>'Akta Lahir (0-17 th)', 'pct'=>$pctAktaLahir017,'jumlah'=>$aktaLahir017Data->get('Memiliki Akta Lahir 0-17 Tahun',0), 'dari'=>'anak 0-17 tahun', 'color'=>'bg-sky-500'],
                    ];
                @endphp
                <select class="form-select text-xs py-1 w-auto" x-model="pilihDok">
                    <option value="">Semua dokumen</option>
                    @foreach ($dokumen as $dok)
                        <option value="{{ $dok['label'] }}">{{ $dok['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($dokumen as $dok)
                    <div class="space-y-1.5" x-show="pilihDok === '' || pilihDok === @js($dok['label'])">
                        <div class="flex justify-between text-xs">
                            <span class="font-medium text-gray-700">{{ $dok['label'] }}</span>
                            <span class="font-bold text-gray-900">{{ $dok['pct'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="{{ $dok['color'] }} h-2.5 rounded-full transition-all" style="width:{{ min($dok['pct'],100) }}%"></div>
                        </div>
                        <p class="text-[10px] text-gray-400">
                            {{ number_format($dok['jumlah'],0,',','.') }} dari total {{ $dok['dari'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- KIA & Akta Lahir — donut Memiliki vs Belum --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="section-card">
                <h2 class="section-title">Kepemilikan KIA</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Anak usia 0–17 tahun</p>
                <div class="h-[250px]"><canvas id="chart-kia"></canvas></div>
                <x-rincian-indikator :data="$kiaData->only(['Memiliki KIA', 'Belum Memiliki KIA'])" :total="$kiaData->get('Jumlah Anak Usia 0-17 Tahun', 0)" chart-id="chart-kia" />
            </div>
            <div class="section-card">
                <h2 class="section-title">Kepemilikan Akta Lahir</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Seluruh usia</p>
                <div class="h-[250px]"><canvas id="chart-akta-lahir"></canvas></div>
                <x-rincian-indikator :data="$aktaLahirData->only(['Memiliki Akta Lahir', 'Belum Memiliki Akta Lahir'])" :total="$aktaLahirData->get('Jumlah Penduduk', 0)" chart-id="chart-akta-lahir" />
            </div>
        </div>

        {{-- Pendidikan + Pekerjaan --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="section-card">
                <h2 class="section-title">Pendidikan</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Jenjang tertinggi yang ditamatkan — diurutkan terbanyak</p>
                <div class="h-[350px]"><canvas id="chart-edu"></canvas></div>
                <x-rincian-indikator :data="$pendidikanData" :total="$pendidikanData->sum()" chart-id="chart-edu" />
            </div>

            <div class="section-card">
                <h2 class="section-title">Pekerjaan</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Jenis pekerjaan/kegiatan utama — diurutkan terbanyak</p>
                <div class="h-[350px]"><canvas id="chart-job"></canvas></div>
                <x-rincian-indikator :data="$pekerjaanData" :total="$pekerjaanData->sum()" chart-id="chart-job" />
            </div>
        </div>

        {{-- Kepemilikan KTP, Kepemilikan KK & Golongan Darah --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="section-card">
                <h2 class="section-title">Kepemilikan KTP</h2>
                <div class="h-[300px]"><canvas id="chart-ktp-status"></canvas></div>
                <x-rincian-indikator :data="$ktpStatusData" :total="$ktpData->get('Wajib KTP', 0)" chart-id="chart-ktp-status" />
            </div>
            <div class="section-card">
                <h2 class="section-title">Kepemilikan KK</h2>
                <div class="h-[300px]"><canvas id="chart-kk-status"></canvas></div>
                <x-rincian-indikator :data="$kkStatusData" :total="$kkData->get('Jumlah Kepala Keluarga', 0)" chart-id="chart-kk-status" />
            </div>
            <div class="section-card">
                <h2 class="section-title">Golongan Darah</h2>
                <div class="h-[300px]"><canvas id="chart-goldar"></canvas></div>
                <x-rincian-indikator :data="$golonganDarahData" :total="$golonganDarahData->sum()" chart-id="chart-goldar" />
            </div>
        </div>

        {{-- Rincian Jenis Pekerjaan + Usia Sekolah --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Rincian Jenis Pekerjaan</h2>
                    <p class="text-xs text-gray-400">17 jenis pekerjaan rinci — melengkapi kelompok besar di atas</p>
                </div>
                <div class="p-4">
                    <x-rincian-indikator :data="$jenisPekerjaanData" :total="$jenisPekerjaanData->sum()" />
                </div>
            </div>

            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Penduduk Usia Sekolah</h2>
                    <p class="text-xs text-gray-400">Berdasarkan jenjang pendidikan</p>
                </div>
                <div class="p-4">
                    <div class="h-48"><canvas id="chart-usia-sekolah"></canvas></div>
                    <x-rincian-indikator :data="$usiaSekolahData" :total="$usiaSekolahData->sum()" chart-id="chart-usia-sekolah" />
                </div>
            </div>
        </div>

        {{-- Agama --}}
        <div class="section-card">
            <h2 class="section-title">Agama</h2>
            <p class="text-xs text-gray-400 -mt-2 mb-3">Agama yang dianut penduduk — diurutkan terbanyak</p>
            <div class="h-[300px]"><canvas id="chart-agama"></canvas></div>
            <x-rincian-indikator :data="$agamaData" :total="$agamaData->sum()" chart-id="chart-agama" />
        </div>

        {{-- Kepala Keluarga + Angkatan Kerja --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <div class="section-card">
                <h2 class="section-title">Kepala Keluarga</h2>
                <p class="text-xs text-gray-400 -mt-2 mb-3">Berdasarkan jenis kelamin</p>
                <div class="flex items-center gap-6">
                    @php $totalKk = $kepalaKeluargaJkData->sum(); @endphp
                    <div class="w-36 h-36 flex-shrink-0"><canvas id="chart-kk-jk"></canvas></div>
                    <div class="space-y-3 flex-1">
                        @foreach ($kepalaKeluargaJkData as $label => $jumlah)
                            @php $pct = $totalKk > 0 ? round($jumlah/$totalKk*100,1) : 0; @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-medium text-gray-700">{{ $label }}</span>
                                    <span class="text-gray-500">{{ number_format($jumlah, 0, ',', '.') }} ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ str_contains($label, 'Laki') ? 'bg-blue-500' : 'bg-pink-500' }}"
                                        style="width:{{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Angkatan Kerja & TPAK</h2>
                    <p class="text-xs text-gray-400">Tingkat Partisipasi Angkatan Kerja, usia 15-64 tahun</p>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="rounded-lg bg-gray-50 p-3">
                            <p class="text-[11px] text-gray-500">Angkatan Kerja</p>
                            <p class="text-lg font-bold text-gray-900">
                                {{ number_format($angkatanKerjaData->get('Angkatan Kerja', 0), 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-brand-50 p-3">
                            <p class="text-[11px] text-brand-700">TPAK</p>
                            <p class="text-lg font-bold text-brand-800">{{ $pctTpak }}%</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400">
                        Dari {{ number_format($angkatanKerjaData->get('Jumlah Penduduk Usia Kerja', 0), 0, ',', '.') }}
                        penduduk usia kerja (15-64 tahun).
                    </p>
                </div>
            </div>
        </div>

        {{-- Kepala Keluarga: Rincian Demografi --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Kepala Keluarga: Rincian Demografi</h2>
                <p class="text-xs text-gray-400">Status perkawinan, pendidikan, kelompok pekerjaan, dan agama para kepala keluarga</p>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2">Status Perkawinan</p>
                    <x-rincian-indikator :data="$kkStatusKawinData" :total="$kkStatusKawinData->sum()" />
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2">Agama</p>
                    <x-rincian-indikator :data="$kkAgamaData" :total="$kkAgamaData->sum()" />
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2">Tingkat Pendidikan</p>
                    <x-rincian-indikator :data="$kkPendidikanData" :total="$kkPendidikanData->sum()" />
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-600 mb-2">Kelompok Pekerjaan</p>
                    <x-rincian-indikator :data="$kkKelPekerjaanData" :total="$kkKelPekerjaanData->sum()" />
                </div>
            </div>
            <div class="px-4 pb-4">
                <p class="text-xs font-semibold text-gray-600 mb-2">Jenis Pekerjaan KTP-EL (99 jenis, se-Kota)</p>
                <x-rincian-indikator :data="$kkPekerjaanData" :total="$kkPekerjaanData->sum()" />
            </div>
        </div>

        {{-- Kepala Keluarga & Agama per Kecamatan menurut Kelompok Umur --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="card">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-sm font-bold text-gray-900">Status Perkawinan KK per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Total lintas kelompok umur — rincian usia ada di Ekspor PDF</p>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-medium">Kecamatan</th>
                                @foreach (['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $label)
                                    <th class="pb-2 font-medium text-right">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kkKawinKuData as $namaKecamatan => $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1.5 text-gray-700">{{ $namaKecamatan }}</td>
                                    @foreach (['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $label)
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
                    <h2 class="text-sm font-bold text-gray-900">Agama per Kecamatan</h2>
                    <p class="text-xs text-gray-400">Total lintas kelompok umur — rincian usia ada di Ekspor PDF</p>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-100">
                                <th class="pb-2 font-medium">Kecamatan</th>
                                @foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Kepercayaan'] as $label)
                                    <th class="pb-2 font-medium text-right">{{ $label }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($agamaKuData as $namaKecamatan => $row)
                                <tr class="border-b border-gray-50">
                                    <td class="py-1.5 text-gray-700">{{ $namaKecamatan }}</td>
                                    @foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Kepercayaan'] as $label)
                                        <td class="py-1.5 text-right text-gray-900">{{ number_format($row->get($label, 0), 0, ',', '.') }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Angkatan Kerja per Tingkat Pendidikan (se-Kota) --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Angkatan Kerja Menurut Tingkat Pendidikan</h2>
                <p class="text-xs text-gray-400">Se-Kota — data ini tidak mengikuti filter wilayah</p>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-100">
                            <th class="pb-2 font-medium">Tingkat Pendidikan</th>
                            <th class="pb-2 font-medium text-right">Jumlah Penduduk</th>
                            <th class="pb-2 font-medium text-right">Angkatan Kerja</th>
                            <th class="pb-2 font-medium text-right">Bekerja</th>
                            <th class="pb-2 font-medium text-right">APAK</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($akPendidikanData as $row)
                            <tr class="border-b border-gray-50">
                                <td class="py-1.5 text-gray-700">{{ $row['jenjang'] }}</td>
                                <td class="py-1.5 text-right text-gray-900">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                                <td class="py-1.5 text-right text-gray-900">{{ number_format($row['angkatan'], 0, ',', '.') }}</td>
                                <td class="py-1.5 text-right text-gray-900">{{ number_format($row['bekerja'], 0, ',', '.') }}</td>
                                <td class="py-1.5 text-right font-medium text-brand-700">{{ $row['apak'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Penerbitan Dokumen (se-Kota, per tahun) --}}
        <div class="card">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-sm font-bold text-gray-900">Penerbitan Dokumen Se-Kota</h2>
                <p class="text-xs text-gray-400">Total setahun terakhir — data ini se-Kota, tidak mengikuti filter wilayah</p>
            </div>
            <div class="p-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($terbitTahunan as $label => $jumlah)
                    <div class="rounded-lg bg-gray-50 p-3 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ number_format($jumlah, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Status Hubungan dalam Keluarga --}}
        <div class="section-card">
            <h2 class="section-title">Status Hubungan dalam Keluarga</h2>
            <p class="text-xs text-gray-400 -mt-2 mb-3">Kedudukan penduduk terhadap kepala keluarga</p>
            <div class="h-[300px]"><canvas id="chart-shbkel"></canvas></div>
            <x-rincian-indikator :data="$shbkelData" :total="$shbkelData->sum()" chart-id="chart-shbkel" />
        </div>

        {{-- Wajib Akta Lahir / KIA / KTP per Kelurahan — stacked (Memiliki
             hijau vs Belum merah muda). SELALU 15 kelurahan terlepas dari
             filter kelurahan/kecamatan yang aktif (begitu memang gunanya). --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="section-card">
                <h2 class="section-title">Wajib Akta Lahir per Kelurahan</h2>
                <div class="h-[420px]"><canvas id="chart-akta-lahir-kelurahan"></canvas></div>
            </div>
            <div class="section-card">
                <h2 class="section-title">Wajib KIA per Kelurahan</h2>
                <div class="h-[420px]"><canvas id="chart-kia-kelurahan"></canvas></div>
            </div>
            <div class="section-card">
                <h2 class="section-title">Wajib KTP per Kelurahan</h2>
                <div class="h-[420px]"><canvas id="chart-ktp-kelurahan"></canvas></div>
            </div>
        </div>

    </div>

    <x-slot:scripts>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const fmt = window.formatAngka;
        const W = window.DadukColors;
        const K = window.DadukKategori;

        function barChart(canvasId, labels, values, opts = {}) {
            const total = values.reduce((a, b) => a + b, 0);
            return new Chart(document.getElementById(canvasId), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: opts.warna ?? labels.map((_, i) => K[i % K.length]),
                        borderRadius: 4,
                        borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                        datalabels: { display: true, anchor: 'end', align: 'end', clamp: true, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                        y: { grid: { display: false }, ticks: { font: { size: 9 } } },
                    },
                },
            });
        }

        function donutCenter(canvasId, labels, values, subtext, warna) {
            const total = values.reduce((a, b) => a + b, 0);
            return new Chart(document.getElementById(canvasId), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: values, backgroundColor: warna, borderWidth: 0, hoverOffset: 4 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    layout: { padding: 16 },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 8, usePointStyle: true, padding: 10, font: { size: 10 } } },
                        tooltip: { callbacks: { label: window.tooltipPersenLabel(total) } },
                        centerText: { display: true, text: fmt(total), subtext },
                        datalabels: { display: true, anchor: 'end', align: 'end', offset: 6, color: '#374151', font: { size: 9, weight: '600' }, formatter: (v) => fmt(v) },
                    },
                },
            });
        }

        // Wajib X per kelurahan — stacked horizontal bar (Memiliki hijau vs Belum merah muda).
        function stackedKelurahan(canvasId, data) {
            const labels = Object.keys(data);
            const memiliki = labels.map((k) => data[k].memiliki ?? 0);
            const belum = labels.map((k) => data[k].belum ?? 0);
            new Chart(document.getElementById(canvasId), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: 'Memiliki', data: memiliki, backgroundColor: W.positif, borderRadius: 3, borderSkipped: false },
                        { label: 'Belum', data: belum, backgroundColor: '#FBCFE8', borderRadius: 3, borderSkipped: false },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } } },
                        tooltip: {
                            callbacks: {
                                label: (c) => {
                                    const t = memiliki[c.dataIndex] + belum[c.dataIndex];
                                    return `${c.dataset.label}: ${fmt(c.raw)} (${window.formatPersen(t > 0 ? c.raw / t * 100 : 0)})`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 }, callback: (v) => fmt(v) } },
                        y: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 } } },
                    },
                },
            });
        }

        // ── Pendidikan, Pekerjaan, Usia Sekolah, Agama, KTP, KK, Golongan Darah, SHBKEL ──
        barChart('chart-edu', @json($pendidikanData->keys()->values()), @json($pendidikanData->values()));
        barChart('chart-job', @json($pekerjaanData->keys()->values()), @json($pekerjaanData->values()));
        barChart('chart-usia-sekolah', @json($usiaSekolahData->keys()->values()), @json($usiaSekolahData->values()));
        barChart('chart-agama', @json($agamaData->keys()->values()), @json($agamaData->values()));
        barChart('chart-ktp-status', @json($ktpStatusData->keys()->values()), @json($ktpStatusData->values()));
        barChart('chart-kk-status', @json($kkStatusData->keys()->values()), @json($kkStatusData->values()));
        barChart('chart-goldar', @json($golonganDarahData->keys()->values()), @json($golonganDarahData->values()));
        barChart('chart-shbkel', @json($shbkelData->keys()->values()), @json($shbkelData->values()));

        // ── KIA & Akta Lahir — donut Memiliki vs Belum ───────────
        donutCenter('chart-kia', ['Memiliki KIA', 'Belum Memiliki KIA'],
            [@json($kiaData->get('Memiliki KIA', 0)), @json($kiaData->get('Belum Memiliki KIA', 0))],
            'anak', [W.positif, '#FBCFE8']);
        donutCenter('chart-akta-lahir', ['Memiliki', 'Belum Memiliki'],
            [@json($aktaLahirData->get('Memiliki Akta Lahir', 0)), @json($aktaLahirData->get('Belum Memiliki Akta Lahir', 0))],
            'jiwa', [W.positif, '#FBCFE8']);

        // ── Kepala Keluarga — donut kecil, L/P ───────────────────
        donutCenter('chart-kk-jk', @json($kepalaKeluargaJkData->keys()->values()), @json($kepalaKeluargaJkData->values()), 'KK', [W.laki, W.perempuan]);

        // ── Wajib Akta Lahir / KIA / KTP per Kelurahan ───────────
        stackedKelurahan('chart-akta-lahir-kelurahan', @json($aktaLahirKelurahanData));
        stackedKelurahan('chart-kia-kelurahan', @json($kiaKelurahanData));
        stackedKelurahan('chart-ktp-kelurahan', @json($ktpKelurahanData));
    });
    </script>
    </x-slot:scripts>

</x-layouts.public>
