{{-- Bagian-bagian Halaman Sosial. Dirender DI DALAM .seksi-grid (flex-wrap)
     oleh dashboard/_grid.blade.php — bersama bagian Demografi — supaya bagian
     bisa dipindah antar halaman, disembunyikan, atau diperkecil, dan sisanya
     mengalir otomatis. Tiap <x-seksi> hanya benar-benar tampil kalau halaman
     DB-nya == halaman yang sedang dibuka. Redraw Chart.js: gambarSemuaChart()
     di dashboard/_skrip, dibaca dari payload `charts`. --}}

@if (($halamanAktif ?? 'sosial') === 'sosial' && $totalPenduduk === 0)
    <div class="seksi-item seksi-w-penuh">
        <div class="alert-info flex items-center gap-2">
            <i class="bi bi-info-circle"></i>
            Tidak ada data untuk filter yang dipilih.
        </div>
    </div>
@endif

<x-seksi halaman="sosial" kunci="dokumen_progress" judul="Kepemilikan Dokumen Kependudukan (progress)" lebar="penuh" :urutan="10">
    <div class="card" x-data="{ pilihDok: '' }">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 class="text-sm font-bold text-gray-900">Kepemilikan Dokumen Kependudukan</h2>
                <p class="text-xs text-gray-400">Periode: {{ $selectedWaktu?->label ?? '-' }}</p>
            </div>
            @php
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
                    <p class="text-[10px] text-gray-400">{{ number_format($dok['jumlah'],0,',','.') }} dari total {{ $dok['dari'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="kia" judul="Kepemilikan KIA" lebar="separuh" :urutan="20">
    <div class="section-card h-full">
        <h2 class="section-title">Kepemilikan KIA</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Anak usia 0–17 tahun</p>
        <div class="h-[250px]"><canvas id="chart-kia"></canvas></div>
        <x-rincian-indikator :data="$kiaData->only(['Memiliki KIA', 'Belum Memiliki KIA'])" :total="$kiaData->get('Jumlah Anak Usia 0-17 Tahun', 0)" chart-id="chart-kia" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="akta_lahir" judul="Kepemilikan Akta Lahir" lebar="separuh" :urutan="30">
    <div class="section-card h-full">
        <h2 class="section-title">Kepemilikan Akta Lahir</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Seluruh usia</p>
        <div class="h-[250px]"><canvas id="chart-akta-lahir"></canvas></div>
        <x-rincian-indikator :data="$aktaLahirData->only(['Memiliki Akta Lahir', 'Belum Memiliki Akta Lahir'])" :total="$aktaLahirData->get('Jumlah Penduduk', 0)" chart-id="chart-akta-lahir" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="pendidikan" judul="Pendidikan" lebar="separuh" :urutan="40">
    <div class="section-card h-full">
        <h2 class="section-title">Pendidikan</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Jenjang tertinggi yang ditamatkan — diurutkan terbanyak</p>
        <div class="h-[350px]"><canvas id="chart-edu"></canvas></div>
        <x-rincian-indikator :data="$pendidikanData" :laki="$pendidikanLakiData" :perempuan="$pendidikanPerempuanData" :total="$pendidikanData->sum()" chart-id="chart-edu" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="pekerjaan" judul="Pekerjaan" lebar="separuh" :urutan="50">
    <div class="section-card h-full">
        <h2 class="section-title">Pekerjaan</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Jenis pekerjaan/kegiatan utama — diurutkan terbanyak</p>
        <div class="h-[350px]"><canvas id="chart-job"></canvas></div>
        <x-rincian-indikator :data="$pekerjaanData" :laki="$pekerjaanLakiData" :perempuan="$pekerjaanPerempuanData" :total="$pekerjaanData->sum()" chart-id="chart-job" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="ktp" judul="Kepemilikan KTP" lebar="sepertiga" :urutan="60">
    <div class="section-card h-full">
        <h2 class="section-title">Kepemilikan KTP</h2>
        <div class="h-[300px]"><canvas id="chart-ktp-status"></canvas></div>
        <x-rincian-indikator :data="$ktpStatusData" :laki="$ktpStatusLakiData" :perempuan="$ktpStatusPerempuanData" :total="$ktpData->get('Wajib KTP', 0)" chart-id="chart-ktp-status" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="kk" judul="Kepemilikan KK" lebar="sepertiga" :urutan="70">
    <div class="section-card h-full">
        <h2 class="section-title">Kepemilikan KK</h2>
        <div class="h-[300px]"><canvas id="chart-kk-status"></canvas></div>
        <x-rincian-indikator :data="$kkStatusData" :total="$kkData->get('Jumlah Kepala Keluarga', 0)" chart-id="chart-kk-status" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="golongan_darah" judul="Golongan Darah" lebar="sepertiga" :urutan="80">
    <div class="section-card h-full">
        <h2 class="section-title">Golongan Darah</h2>
        <div class="h-[300px]"><canvas id="chart-goldar"></canvas></div>
        <x-rincian-indikator :data="$golonganDarahData" :laki="$golonganDarahLakiData" :perempuan="$golonganDarahPerempuanData" :total="$golonganDarahData->sum()" chart-id="chart-goldar" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="jenis_pekerjaan" judul="Rincian Jenis Pekerjaan" lebar="separuh" :urutan="90">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Rincian Jenis Pekerjaan</h2>
            <p class="text-xs text-gray-400">17 jenis pekerjaan rinci — melengkapi kelompok besar di atas</p>
        </div>
        <div class="p-4">
            <x-rincian-indikator :data="$jenisPekerjaanData" :total="$jenisPekerjaanData->sum()" />
        </div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="usia_sekolah" judul="Penduduk Usia Sekolah" lebar="separuh" :urutan="100">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Penduduk Usia Sekolah</h2>
            <p class="text-xs text-gray-400">Berdasarkan jenjang pendidikan</p>
        </div>
        <div class="p-4">
            <div class="h-48"><canvas id="chart-usia-sekolah"></canvas></div>
            <x-rincian-indikator :data="$usiaSekolahData" :total="$usiaSekolahData->sum()" chart-id="chart-usia-sekolah" />
        </div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="agama" judul="Agama" lebar="penuh" :urutan="110">
    <div class="section-card">
        <h2 class="section-title">Agama</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Agama yang dianut penduduk — diurutkan terbanyak</p>
        <div class="h-[300px]"><canvas id="chart-agama"></canvas></div>
        <x-rincian-indikator :data="$agamaData" :laki="$agamaLakiData" :perempuan="$agamaPerempuanData" :total="$agamaData->sum()" chart-id="chart-agama" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="kepala_keluarga" judul="Kepala Keluarga (menurut jenis kelamin)" lebar="penuh" :urutan="120">
    <div class="section-card">
        <h2 class="section-title">Kepala Keluarga</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Berdasarkan jenis kelamin</p>
        <div class="flex items-center gap-8">
            @php $totalKk = $kepalaKeluargaJkData->sum(); @endphp
            <div class="w-52 h-52 flex-shrink-0"><canvas id="chart-kk-jk"></canvas></div>
            <div class="space-y-3 flex-1">
                @foreach ($kepalaKeluargaJkData as $label => $jumlah)
                    @php $pct = $totalKk > 0 ? round($jumlah/$totalKk*100,1) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-gray-700">{{ $label }}</span>
                            <span class="text-gray-500">{{ number_format($jumlah, 0, ',', '.') }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full {{ str_contains($label, 'Laki') ? 'bg-blue-500' : 'bg-pink-500' }}" style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="angkatan_kerja" judul="Angkatan Kerja & TPAK" lebar="penuh" :urutan="130">
    <div class="card">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Angkatan Kerja & TPAK</h2>
            <p class="text-xs text-gray-400">Tingkat Partisipasi Angkatan Kerja, usia 15-64 tahun</p>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="rounded-lg bg-gray-50 p-3">
                    <p class="text-[11px] text-gray-500">Angkatan Kerja</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($angkatanKerjaData->get('Angkatan Kerja', 0), 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-brand-50 p-3">
                    <p class="text-[11px] text-brand-700">TPAK</p>
                    <p class="text-lg font-bold text-brand-800">{{ $pctTpak }}%</p>
                </div>
            </div>
            <p class="text-[11px] text-gray-400">Dari {{ number_format($angkatanKerjaData->get('Jumlah Penduduk Usia Kerja', 0), 0, ',', '.') }} penduduk usia kerja (15-64 tahun).</p>
        </div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="kk_rincian" judul="Kepala Keluarga: Rincian Demografi" lebar="penuh" :urutan="140">
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
</x-seksi>

<x-seksi halaman="sosial" kunci="kk_kawin_kecamatan" judul="Status Perkawinan KK per Kecamatan" lebar="separuh" :urutan="150">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Status Perkawinan KK per Kecamatan</h2>
            <p class="text-xs text-gray-400">Total lintas kelompok umur — rincian usia ada di Unduh PDF</p>
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
</x-seksi>

<x-seksi halaman="sosial" kunci="agama_kecamatan" judul="Agama per Kecamatan" lebar="separuh" :urutan="160">
    <div class="card h-full">
        <div class="p-4 border-b border-gray-100">
            <h2 class="text-sm font-bold text-gray-900">Agama per Kecamatan</h2>
            <p class="text-xs text-gray-400">Total lintas kelompok umur — rincian usia ada di Unduh PDF</p>
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
</x-seksi>

<x-seksi halaman="sosial" kunci="ak_pendidikan" judul="Angkatan Kerja Menurut Tingkat Pendidikan" lebar="penuh" :urutan="170">
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
                        <th class="pb-2 font-medium text-right">Bukan Angkatan Kerja</th>
                        <th class="pb-2 font-medium text-right">Bekerja</th>
                        <th class="pb-2 font-medium text-right">Tidak Bekerja</th>
                        <th class="pb-2 font-medium text-right">APAK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($akPendidikanData as $row)
                        <tr class="border-b border-gray-50">
                            <td class="py-1.5 text-gray-700">{{ $row['jenjang'] }}</td>
                            <td class="py-1.5 text-right text-gray-900">{{ number_format($row['jumlah'], 0, ',', '.') }}</td>
                            <td class="py-1.5 text-right text-gray-900">{{ number_format($row['angkatan'], 0, ',', '.') }}</td>
                            <td class="py-1.5 text-right text-gray-900">{{ number_format($row['bukan_ak'], 0, ',', '.') }}</td>
                            <td class="py-1.5 text-right text-gray-900">{{ number_format($row['bekerja'], 0, ',', '.') }}</td>
                            <td class="py-1.5 text-right text-gray-900">{{ number_format($row['tidak_bekerja'], 0, ',', '.') }}</td>
                            <td class="py-1.5 text-right font-medium text-brand-700">{{ $row['apak'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="penerbitan_dokumen" judul="Penerbitan Dokumen Se-Kota" lebar="penuh" :urutan="180">
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
</x-seksi>

<x-seksi halaman="sosial" kunci="shbkel" judul="Status Hubungan dalam Keluarga" lebar="penuh" :urutan="190">
    <div class="section-card">
        <h2 class="section-title">Status Hubungan dalam Keluarga</h2>
        <p class="text-xs text-gray-400 -mt-2 mb-3">Kedudukan penduduk terhadap kepala keluarga</p>
        <div class="h-[300px]"><canvas id="chart-shbkel"></canvas></div>
        <x-rincian-indikator :data="$shbkelData" :laki="$shbkelLakiData" :perempuan="$shbkelPerempuanData" :total="$shbkelData->sum()" chart-id="chart-shbkel" />
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="akta_lahir_kelurahan" judul="Wajib Akta Lahir per Kelurahan" lebar="sepertiga" :urutan="200">
    <div class="section-card h-full">
        <h2 class="section-title">Wajib Akta Lahir per Kelurahan</h2>
        <div class="h-[420px]"><canvas id="chart-akta-lahir-kelurahan"></canvas></div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="kia_kelurahan" judul="Wajib KIA per Kelurahan" lebar="sepertiga" :urutan="210">
    <div class="section-card h-full">
        <h2 class="section-title">Wajib KIA per Kelurahan</h2>
        <div class="h-[420px]"><canvas id="chart-kia-kelurahan"></canvas></div>
    </div>
</x-seksi>

<x-seksi halaman="sosial" kunci="ktp_kelurahan" judul="Wajib KTP per Kelurahan" lebar="sepertiga" :urutan="220">
    <div class="section-card h-full">
        <h2 class="section-title">Wajib KTP per Kelurahan</h2>
        <div class="h-[420px]"><canvas id="chart-ktp-kelurahan"></canvas></div>
    </div>
</x-seksi>
