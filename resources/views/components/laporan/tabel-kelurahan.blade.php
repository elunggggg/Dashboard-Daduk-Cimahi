{{--
    Tabel standar buku profil: No | Kecamatan | Kelurahan | kolom... | (Jumlah) | baris KOTA CIMAHI.
    $data: array wilayah_id => [label => nilai] (hasil ProfilKependudukanController::perWilayah()).
    $kolom: daftar label yang ditampilkan, berurutan.
    $totalKolom: true untuk menambah kolom "Jumlah" (SUM antar $kolom) — hanya masuk akal
                 untuk tabel jenis HITUNGAN (L/P/dst), bukan tabel RASIO/MEDIAN.
--}}
@props(['wilayahList', 'data', 'kolom', 'totalKolom' => false, 'satuan' => null])
<table>
    <thead>
        <tr>
            <th style="width:18px">No</th>
            <th>Kecamatan</th>
            <th>Kelurahan</th>
            @foreach($kolom as $k)<th class="num">{{ $k }}</th>@endforeach
            @if($totalKolom)<th class="num">Jumlah</th>@endif
        </tr>
    </thead>
    <tbody>
        @php $totalPerKolom = array_fill_keys($kolom, 0); $grandTotal = 0; @endphp
        @foreach($wilayahList as $i => $w)
            @php
                $nilai = $data[$w->id] ?? [];
                $baris = 0;
            @endphp
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $w->nama_kecamatan }}</td>
                <td>{{ $w->nama_kelurahan }}</td>
                @foreach($kolom as $k)
                    @php
                        $v = $nilai[$k] ?? null;
                        if ($v !== null) { $totalPerKolom[$k] += $v; $baris += $v; }
                    @endphp
                    <td class="num">{{ $v !== null ? number_format($v, 0, ',', '.') : '—' }}</td>
                @endforeach
                @if($totalKolom)
                    @php $grandTotal += $baris; @endphp
                    <td class="num strong">{{ number_format($baris, 0, ',', '.') }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="strong">
            <td colspan="3">KOTA CIMAHI</td>
            @foreach($kolom as $k)<td class="num">{{ number_format($totalPerKolom[$k], 0, ',', '.') }}</td>@endforeach
            @if($totalKolom)<td class="num">{{ number_format($grandTotal, 0, ',', '.') }}</td>@endif
        </tr>
    </tfoot>
</table>
@if($satuan)
    <p class="ket">Satuan: {{ $satuan }}</p>
@endif
