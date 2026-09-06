{{-- Template dompdf: CSS harus sederhana & inline-friendly, dompdf tidak
     mendukung flexbox/grid maupun stylesheet Tailwind hasil build. Palet &
     bahasa visual disamakan dengan laporan/profil-pdf.blade.php (navy
     #1E3A5F + aksen teal #0D9488) supaya kedua jenis ekspor PDF terasa satu
     keluarga, bukan dua gaya berbeda. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }}</title>
    <style>
        @page { margin: 26mm 12mm 16mm 12mm; }
        body  { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }

        /* Kop surat — position:fixed diulang dompdf di setiap halaman. */
        .kop {
            position: fixed; top: -20mm; left: 0; right: 0; height: 16mm;
            background: #1e3a5f; color: #ffffff; padding: 6px 12px;
        }
        .kop .judul   { font-size: 13px; font-weight: bold; margin: 0; }
        .kop .subjudul{ font-size: 8px; color: #cbd5e1; margin: 2px 0 0; }
        .kop .aksen   { position: fixed; top: -4mm; left: 0; right: 0; height: 1.5mm; background: #0d9488; }

        .meta { font-size: 8px; color: #6b7280; margin: 2px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        th    { background: #1e3a5f; color: #ffffff; border: 1px solid #1e3a5f; padding: 5px 6px;
                text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: .3px; }
        td    { border: 1px solid #e5e7eb; padding: 4px 6px; }
        tr    { page-break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        .num  { text-align: right; }
        .foot { margin-top: 10px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>

    <div class="kop">
        <p class="judul">DADUK Cimahi — Dashboard Data Agregat Penduduk</p>
        <p class="subjudul">Dinas Kependudukan dan Pencatatan Sipil Kota Cimahi</p>
    </div>
    <div class="aksen"></div>

    <h1 style="font-size:14px;margin:0 0 2px;color:#1e3a5f;">{{ $judul }}</h1>
    <div class="meta">
        Dicetak {{ $dicetak->translatedFormat('d F Y H:i') }} oleh {{ $oleh }} ·
        {{ number_format($total, 0, ',', '.') }} baris
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Kecamatan</th>
                <th>Kelurahan</th>
                <th>Periode</th>
                <th>Indikator</th>
                <th>Kategori</th>
                <th class="num">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($baris as $b)
                <tr>
                    <td>{{ $b->wilayah->kode_kemendagri ?? '—' }}</td>
                    <td>{{ $b->wilayah->nama_kecamatan ?? '—' }}</td>
                    <td>{{ $b->wilayah->nama_kelurahan ?? '—' }}</td>
                    <td>{{ $b->waktu->label ?? '—' }}</td>
                    <td>{{ $b->kategori->jenis_indikator ?? '—' }}</td>
                    <td>{{ $b->kategori->label ?? '—' }}</td>
                    <td class="num">{{ number_format($b->jumlah, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">
        Seluruh data bersifat agregat/rekap — tidak memuat data personal maupun NIK.
    </div>

</body>
</html>
