<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Kependudukan Kota Cimahi — {{ $waktu->label }}</title>
    <style>
        @page { margin: 20mm 15mm 18mm 15mm; }
        body  { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 24px 0 2px; border-bottom: 2px solid #1e3a5f; padding-bottom: 3px; }
        h3 { font-size: 11px; margin: 14px 0 4px; color: #1e3a5f; }
        p  { margin: 3px 0; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0 8px; }
        thead { display: table-header-group; }
        th  { background: #eef2f7; border: 1px solid #cbd5e1; padding: 3px 5px; text-align: left; font-size: 8px; }
        td  { border: 1px solid #e2e8f0; padding: 3px 5px; font-size: 8px; }
        tfoot td { background: #f8fafc; }
        .num { text-align: right; }
        .strong { font-weight: bold; }
        .ket { font-size: 7.5px; color: #6b7280; font-style: italic; margin: 0 0 10px; }
        .kosong { padding: 10px; background: #fff7ed; border: 1px dashed #f59e0b; color: #92400e; font-size: 8.5px; }
        .cover { text-align: center; padding-top: 120px; }
        .cover h1 { font-size: 24px; }
        .kpi-row { width: 100%; margin: 8px 0 14px; }
        .kpi-row td { border: none; padding: 6px 10px; text-align: center; background: #f8fafc; }
        .kpi-row .angka { font-size: 16px; font-weight: bold; color: #1e3a5f; display: block; }
        .kpi-row .label { font-size: 7.5px; color: #6b7280; }
        .pagebreak { page-break-before: always; }
        .footer-note { font-size: 7.5px; color: #9ca3af; margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>

    {{-- ── Sampul ── --}}
    <div class="cover">
        <h1>PROFIL KEPENDUDUKAN<br>KOTA CIMAHI</h1>
        <p style="margin-top:20px;font-size:12px;">Data Agregat Kependudukan {{ $waktu->label }}</p>
        <p style="font-size:9px;color:#6b7280;">Disusun otomatis oleh Sistem DADUK Cimahi<br>
            Dinas Kependudukan dan Pencatatan Sipil Kota Cimahi<br>
            Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="pagebreak"></div>

    {{-- ── BAB I: Pendahuluan ── --}}
    <h2>BAB I — PENDAHULUAN</h2>
    <p>
        Buku Profil Kependudukan Kota Cimahi ini disusun berdasarkan data agregat kependudukan
        yang tercatat dalam Sistem DADUK Cimahi untuk periode <strong>{{ $waktu->label }}</strong>.
        Laporan ini disusun mengikuti kerangka Buku Profil Kependudukan resmi Disdukcapil Kota
        Cimahi, dengan data yang bersumber dari berkas DKB (Data Kependudukan Berbasis) yang
        telah diunggah ke dalam sistem.
    </p>
    <p class="ket">
        Catatan kejujuran data: laporan ini HANYA memuat periode yang benar-benar sudah diunggah
        ke sistem ({{ \App\Models\DimWaktu::orderBy('tahun')->orderBy('semester')->pluck('label')->implode(', ') }}).
        Tabel yang memerlukan data tren multi-tahun (2022-2024) atau indikator yang belum
        dihitung sistem (SMAM) ditandai "Data tidak tersedia" — bukan dikosongkan diam-diam.
    </p>

    <div class="pagebreak"></div>

    {{-- ── BAB II: Gambaran Umum Wilayah ── --}}
    <h2>BAB II — GAMBARAN UMUM WILAYAH</h2>
    <p>
        Kota Cimahi terdiri dari 3 kecamatan dan 15 kelurahan. Tabel berikut menyajikan luas
        wilayah masing-masing kelurahan sebagaimana tercatat dalam data agregat kependudukan.
    </p>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$kepadatan" :kolom="['Luas Wilayah (km2)']" satuan="km²" />

    <div class="pagebreak"></div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    <h2>BAB III — GAMBARAN UMUM KEPENDUDUKAN</h2>

    <h3>3.1 Jumlah Penduduk Menurut Wilayah dan Jenis Kelamin</h3>
    <table class="kpi-row"><tr>
        <td><span class="angka">{{ number_format($totalPenduduk,0,',','.') }}</span><span class="label">Total Penduduk</span></td>
        <td><span class="angka">{{ number_format($penduduk->pluck('Laki-laki')->sum(),0,',','.') }}</span><span class="label">Laki-laki</span></td>
        <td><span class="angka">{{ number_format($penduduk->pluck('Perempuan')->sum(),0,',','.') }}</span><span class="label">Perempuan</span></td>
    </tr></table>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$penduduk" :kolom="['Laki-laki','Perempuan']" :total-kolom="true" satuan="jiwa" />

    <h3>3.2 Kepadatan Penduduk</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$kepadatan" :kolom="['Kepadatan (Jiwa/km2)']" satuan="jiwa/km²" />

    <h3>3.3 Laju Pertumbuhan Penduduk (LPP)</h3>
    @if(collect($lpp)->isEmpty())
        <p class="kosong">Data tidak tersedia — LPP memerlukan data penduduk periode sebelumnya (S2 tahun lalu) yang tidak diunggah ke sistem ini.</p>
    @else
        <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$lpp" :kolom="['Laju Pertumbuhan Penduduk (%)']" satuan="%" />
    @endif

    <div class="pagebreak"></div>

    <h3>3.4 Piramida Penduduk (Estimasi)</h3>
    <p class="ket">
        KelompokUmur tersimpan sebagai total (L+P), bukan per jenis kelamin — piramida berikut
        adalah ESTIMASI dengan memecah setiap kelompok umur memakai rasio jenis kelamin kota
        secara keseluruhan, BUKAN data mentah per kelompok umur per jenis kelamin.
    </p>
    @php
        $maxPiramida = max(1, collect($piramida)->max(fn($p) => max($p['laki'], $p['perempuan'])));
        $lebarMax = 180;
    @endphp
    <table>
        <thead><tr><th style="width:120px" class="num">Laki-laki</th><th style="width:70px">Umur</th><th>Perempuan</th></tr></thead>
        <tbody>
        @foreach($piramida as $p)
            <tr>
                <td class="num" style="padding:1px 4px;">
                    <svg width="180" height="10"><rect x="{{ 180 - ($p['laki']/$maxPiramida*$lebarMax) }}" y="0" width="{{ $p['laki']/$maxPiramida*$lebarMax }}" height="9" fill="#3b82f6" /></svg>
                    {{ number_format($p['laki'],0,',','.') }}
                </td>
                <td style="text-align:center;font-size:7.5px;">{{ $p['label'] }}</td>
                <td style="padding:1px 4px;">
                    <svg width="180" height="10"><rect x="0" y="0" width="{{ $p['perempuan']/$maxPiramida*$lebarMax }}" height="9" fill="#ec4899" /></svg>
                    {{ number_format($p['perempuan'],0,',','.') }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>3.5 Rasio Jenis Kelamin (Sex Ratio)</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$rasioKelamin" :kolom="['Rasio Jenis Kelamin']" satuan="laki-laki per 100 perempuan" />

    <div class="pagebreak"></div>

    <h3>3.6 Kelompok Usia Muda, Produktif, Usia Tua &amp; Rasio Ketergantungan</h3>
    <table class="kpi-row"><tr>
        <td><span class="angka">{{ $dependencyRatio !== null ? number_format($dependencyRatio,1,',','.').'%' : '—' }}</span><span class="label">Rasio Ketergantungan Kota</span></td>
    </tr></table>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$ketergantungan"
        :kolom="['Usia Muda (0-14 Tahun)','Usia Produktif (15-64 Tahun)','Usia Tua (65+ Tahun)']" satuan="jiwa" />
    <p class="ket">Rasio Ketergantungan Kota = (Usia Muda + Usia Tua) ÷ Usia Produktif × 100, dihitung dari total kota (bukan rata-rata kelurahan).</p>

    <h3>3.7 Umur Median</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$umurMedian" :kolom="['Umur Median Laki-laki','Umur Median Perempuan','Umur Median']" satuan="tahun" />

    <div class="pagebreak"></div>

    <h3>3.8 Status Perkawinan</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$statKawin" :kolom="['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati']" :total-kolom="true" satuan="jiwa, usia 10+ tahun" />

    <h3>3.9 Rata-Rata Umur Kawin Pertama (SMAM)</h3>
    <p class="kosong">
        Data tidak tersedia — SMAM dihitung dengan metode Hajnal dari proporsi belum-kawin per
        kelompok umur. Sumber sheet-nya (StatPerkawinanKUfix) berformat pivot yang tidak
        konsisten sehingga belum dihitung sistem ini demi menghindari angka yang berisiko keliru.
    </p>

    <h3>3.10 Kelahiran — Angka Kelahiran Kasar (CBR) &amp; Angka Kelahiran Umum (GFR)</h3>
    <table class="kpi-row"><tr>
        <td><span class="angka">{{ $cbr !== null ? number_format($cbr,2,',','.') : '—' }}</span><span class="label">CBR (per 1.000 penduduk)</span></td>
        <td><span class="angka">{{ $gfr !== null ? number_format($gfr,2,',','.') : '—' }}</span><span class="label">GFR (per 1.000 perempuan 15-49 th)</span></td>
    </tr></table>
    <p class="ket">
        DKB tidak mencatat jumlah kelahiran secara langsung — jumlah penduduk usia 0 tahun
        dipakai sebagai proksi (metode yang sama dipakai buku profil resmi). Angka usia 0
        diketahui cenderung under-registrasi.
    </p>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$usia0" :kolom="['Jumlah Penduduk Usia 0 Tahun']" satuan="jiwa" />

    <h3>3.11 Rasio Anak dan Perempuan (Child Women Ratio/CWR)</h3>
    <p class="kosong">Data tidak tersedia dalam bentuk yang bisa dihitung akurat oleh sistem ini pada periode ini.</p>

    <div class="pagebreak"></div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    <h2>BAB IV — KUALITAS PENDUDUK</h2>

    <h3>4.1 Pendidikan</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$pendidikan"
        :kolom="['Tidak/Belum Sekolah','Belum Tamat SD','Tamat SD/Sederajat','SMP/Sederajat','SMA/Sederajat','Diploma I/II','Diploma III','Diploma IV/S1','S2','S3']"
        :total-kolom="true" satuan="jiwa" />

    <div class="pagebreak"></div>

    <h3>4.2 Ekonomi</h3>
    <h3 style="font-size:9.5px;">4.2.1 Pekerjaan (Kelompok)</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$pekerjaan"
        :kolom="['Aparatur/Pejabat Negara','Tenaga Pengajar','Wiraswasta','Pertanian/Peternakan','Nelayan','Agama dan Kepercayaan','Pelajar/Mahasiswa','Tenaga Kesehatan','Belum/Tidak Bekerja','Pensiunan','Lainnya']"
        :total-kolom="true" satuan="jiwa" />

    <h3 style="font-size:9.5px;">4.2.2 Angkatan Kerja &amp; Tingkat Partisipasi Angkatan Kerja (TPAK)</h3>
    <table class="kpi-row"><tr>
        <td><span class="angka">{{ $tpak !== null ? number_format($tpak,2,',','.').'%' : '—' }}</span><span class="label">TPAK Kota</span></td>
    </tr></table>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$angkatanKerja" :kolom="['Jumlah Penduduk Usia Kerja','Angkatan Kerja']" satuan="jiwa, usia 15-64 tahun" />

    <h3 style="font-size:9.5px;">4.2.3 Pengangguran / Tingkat Pengangguran</h3>
    <p class="kosong">Data tidak tersedia secara terpisah — sheet PENGANGGURAN/STATUS_PEKERJAAN belum diunggah ke sistem ini.</p>

    <div class="pagebreak"></div>

    <h3>4.3 Keluarga</h3>
    <h3 style="font-size:9.5px;">4.3.1 Jumlah Kepala Keluarga</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$kk" :kolom="['Jumlah Kepala Keluarga']" satuan="KK" />

    <h3 style="font-size:9.5px;">4.3.2 Kepala Keluarga Menurut Jenis Kelamin</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$kkJk" :kolom="['Kepala Keluarga Laki-laki','Kepala Keluarga Perempuan']" :total-kolom="true" satuan="KK" />

    <h3 style="font-size:9.5px;">4.3.3 Status Hubungan dalam Keluarga</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$shbkel"
        :kolom="['Kepala Keluarga','Suami','Isteri','Anak','Menantu','Cucu','Orang Tua','Mertua','Famili Lain','Pembantu','Lainnya']"
        :total-kolom="true" satuan="jiwa" />

    <div class="pagebreak"></div>

    <h3>4.4 Sosial</h3>
    <h3 style="font-size:9.5px;">4.4.1 Agama</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$agama" :kolom="['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Kepercayaan']" :total-kolom="true" satuan="jiwa" />

    <h3 style="font-size:9.5px;">4.4.2 Disabilitas</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$disabilitas"
        :kolom="['Disabilitas Fisik','Disabilitas Netra/Buta','Disabilitas Rungu/Wicara','Disabilitas Mental/Jiwa','Disabilitas Fisik & Mental','Disabilitas Lainnya']"
        :total-kolom="true" satuan="jiwa" />

    <div class="pagebreak"></div>

    <h3 style="font-size:9.5px;">4.4.3 Golongan Darah</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$golDarah" :kolom="['A','B','AB','O','Tidak Tahu']" :total-kolom="true" satuan="jiwa" />

    <p class="ket">
        Total golongan darah tidak akan sama persis dengan total penduduk — kolom rhesus
        (A+, A−, dst.) tidak dipetakan sistem, hanya golongan pokok A/B/AB/O/Tidak Tahu.
    </p>

    <div class="pagebreak"></div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    <h2>BAB V — MOBILITAS PENDUDUK</h2>
    <table class="kpi-row"><tr>
        <td><span class="angka">{{ number_format($datang->pluck('Datang')->sum(),0,',','.') }}</span><span class="label">Total Datang</span></td>
        <td><span class="angka">{{ number_format($pindah->pluck('Pindah')->sum(),0,',','.') }}</span><span class="label">Total Pindah</span></td>
        <td><span class="angka">{{ number_format($datang->pluck('Datang')->sum() - $pindah->pluck('Pindah')->sum(),0,',','.') }}</span><span class="label">Saldo Migrasi</span></td>
    </tr></table>

    <h3>5.1 Penduduk Datang</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$datang" :kolom="['Datang']" satuan="jiwa" />

    <h3>5.2 Penduduk Pindah</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$pindah" :kolom="['Pindah']" satuan="jiwa" />

    <h3>5.3 Rasio Pindah-Datang</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$rasioPindahDatang" :kolom="['Rasio Pindah-Datang']" satuan="Pindah per 100 Datang" />

    <div class="pagebreak"></div>

    {{-- ══════════════════════════════════════════════════════════ --}}
    <h2>BAB VI — KEPEMILIKAN DOKUMEN KEPENDUDUKAN</h2>

    <h3>6.1 Kartu Keluarga</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$kk" :kolom="['KK Sudah TTE','KK Belum TTE']" :total-kolom="true" satuan="KK" />

    <h3>6.2 Kartu Tanda Penduduk Elektronik (KTP-el)</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$ktp" :kolom="['Wajib KTP','Sudah Rekam KTP','Belum Rekam KTP','Sudah Cetak KTP','Belum Cetak KTP']" satuan="jiwa" />

    <div class="pagebreak"></div>

    <h3>6.3 Kartu Identitas Anak (KIA)</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$kia" :kolom="['Memiliki KIA','Belum Memiliki KIA','Jumlah Anak Usia 0-17 Tahun']" satuan="jiwa" />

    <h3>6.4 Akta Kelahiran</h3>
    <h3 style="font-size:9.5px;">6.4.1 Seluruh Usia</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$aktaLahir" :kolom="['Memiliki Akta Lahir','Belum Memiliki Akta Lahir']" :total-kolom="true" satuan="jiwa" />
    <h3 style="font-size:9.5px;">6.4.2 Usia 0-5 Tahun</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$aktaLahir05" :kolom="['Memiliki Akta Lahir 0-5 Tahun','Belum Memiliki Akta Lahir 0-5 Tahun']" :total-kolom="true" satuan="jiwa" />

    <div class="pagebreak"></div>
    <h3 style="font-size:9.5px;">6.4.3 Usia 0-17 Tahun</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$aktaLahir017" :kolom="['Memiliki Akta Lahir 0-17 Tahun','Belum Memiliki Akta Lahir 0-17 Tahun']" :total-kolom="true" satuan="jiwa" />

    <h3>6.5 Akta Perkawinan</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$aktaKawin" :kolom="['Memiliki Akta Kawin','Belum Memiliki Akta Kawin']" :total-kolom="true" satuan="jiwa berstatus kawin" />

    <h3>6.6 Akta Perceraian</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$aktaCerai" :kolom="['Memiliki Akta Cerai','Belum Memiliki Akta Cerai']" :total-kolom="true" satuan="jiwa berstatus cerai" />

    <div class="pagebreak"></div>

    <h3>6.7 Penduduk Warga Negara Asing (WNA)</h3>
    <x-laporan.tabel-kelurahan :wilayah-list="$wilayahList" :data="$wna" :kolom="['WNA Laki-laki','WNA Perempuan','WNA Jumlah']" satuan="jiwa" />

    {{-- ══════════════════════════════════════════════════════════ --}}
    <h2>BAB VII — PENUTUP</h2>
    <p>
        Buku Profil Kependudukan Kota Cimahi periode {{ $waktu->label }} ini disusun secara
        otomatis dari data agregat kependudukan yang tersimpan dalam Sistem DADUK Cimahi.
        Diharapkan laporan ini dapat menjadi rujukan dalam perumusan kebijakan pembangunan
        dan pelayanan kepada masyarakat.
    </p>

    <div class="footer-note">
        Sumber: Dinas Kependudukan dan Pencatatan Sipil Kota Cimahi — Sistem DADUK Cimahi ·
        Dicetak {{ now()->translatedFormat('d F Y H:i') }} · Periode data: {{ $waktu->label }}
    </div>

</body>
</html>
