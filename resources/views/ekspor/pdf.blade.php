{{-- Template dompdf: CSS harus sederhana & inline-friendly, dompdf tidak
     mendukung flexbox/grid maupun stylesheet Tailwind hasil build. Palet &
     bahasa visual disamakan dengan laporan/profil-pdf.blade.php (navy
     #1E3A5F + aksen teal #0D9488) supaya kedua jenis ekspor PDF terasa satu
     keluarga, bukan dua gaya berbeda.

     Kolom ($kolom) & kop ($kopJudul/$kopSubjudul) berasal dari Konfigurasi
     Export. $baris = array asosiatif [kunci_kolom => nilai] dari
     DataAgregatExport::barisTampil() — sama persis dengan sumber Excel. --}}
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
        <p class="judul">{{ $kopJudul }}</p>
        <p class="subjudul">{{ $kopSubjudul }}</p>
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
                @foreach ($kolom as $k)
                    <th @class(['num' => $k->format === \App\Models\KonfigurasiExport::FORMAT_ANGKA])>{{ $k->label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($baris as $b)
                <tr>
                    @foreach ($kolom as $k)
                        @php $nilai = $b[$k->kunci] ?? null; @endphp
                        @if ($k->format === \App\Models\KonfigurasiExport::FORMAT_ANGKA)
                            <td class="num">{{ $nilai === null ? '—' : number_format((int) $nilai, 0, ',', '.') }}</td>
                        @else
                            <td>{{ $nilai === null || $nilai === '' ? '—' : $nilai }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">
        Seluruh data bersifat agregat/rekap — tidak memuat data personal maupun NIK.
    </div>

</body>
</html>
