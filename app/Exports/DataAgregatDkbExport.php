<?php

namespace App\Exports;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\Metadata;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Ekspor Excel bergaya berkas DKB Disdukcapil: SATU sheet per jenis_indikator
 * (lihat DkbSheetExport untuk tata letak tiap sheet). Menggantikan ekspor
 * Excel "rata" (satu sheet, semua baris) — pilihan format PDF tetap memakai
 * tata letak rata + Konfigurasi Export.
 *
 * Filter yang berlaku:
 *  - periode  : satu periode → satu blok per sheet; kosong → semua periode,
 *               tiap periode jadi blok tabel sendiri yang ditumpuk.
 *  - kecamatan: batasi baris wilayah ke kecamatan itu saja.
 *  - indikator: satu jenis → satu sheet; kosong → semua jenis yang ada datanya.
 */
class DataAgregatDkbExport implements WithMultipleSheets
{
    public function __construct(
        private readonly ?int $waktuId = null,
        private readonly ?string $kecamatan = null,
        private readonly ?string $jenisIndikator = null,
    ) {
    }

    /** @return array<int, DkbSheetExport> */
    public function sheets(): array
    {
        $periode = $this->waktuId
            ? DimWaktu::where('id', $this->waktuId)->get()
            : DimWaktu::orderBy('tahun')->orderBy('semester')->get();

        $namaTerbaca = Metadata::pluck('nama', 'jenis_indikator');

        $sheets = [];
        $dipakai = [];

        foreach ($this->daftarJenis() as $jenis) {
            if (! $this->adaData($jenis)) {
                continue;
            }

            $judul  = $namaTerbaca->get($jenis) ?: Str::headline($jenis);
            $nama   = $this->namaSheet($judul !== '' ? $judul : $jenis, $dipakai);

            $sheets[] = new DkbSheetExport($jenis, $judul, $periode, $this->kecamatan, $nama);
        }

        // Selalu kembalikan minimal satu sheet supaya berkas tidak korup.
        if ($sheets === []) {
            $sheets[] = new DkbSheetExport('__kosong__', 'Tidak Ada Data', collect(), $this->kecamatan, 'Tidak Ada Data');
        }

        return $sheets;
    }

    /**
     * Jenis indikator yang jadi sheet. Bila difilter → satu itu saja. Bila
     * tidak → semua jenis dasar (varian _l/_p disatukan ke induknya).
     *
     * @return array<int, string>
     */
    private function daftarJenis(): array
    {
        if ($this->jenisIndikator) {
            // Kalau kebetulan yang dipilih varian _l/_p, pakai induknya.
            $j = preg_replace('/_(l|p)$/', '', $this->jenisIndikator);

            return [$j];
        }

        $semua = DimKategori::query()->distinct()->orderBy('jenis_indikator')->pluck('jenis_indikator');

        return $semua
            ->reject(fn (string $j) => (str_ends_with($j, '_l') || str_ends_with($j, '_p'))
                && $semua->contains(substr($j, 0, -2)))
            ->values()
            ->all();
    }

    private function adaData(string $jenis): bool
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis))
            ->when($this->waktuId, fn ($q) => $q->where('waktu_id', $this->waktuId))
            ->when($this->kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($w) => $w->where('nama_kecamatan', $this->kecamatan)))
            ->exists();
    }

    /**
     * Nama tab sheet yang sah untuk Excel: ≤31 karakter, tanpa \ / ? * [ ] : ,
     * dan unik di dalam berkas.
     *
     * @param  array<string, bool>  $dipakai
     */
    private function namaSheet(string $dasar, array &$dipakai): string
    {
        $bersih = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $dasar);
        $bersih = trim(preg_replace('/\s+/', ' ', $bersih));
        $bersih = mb_substr($bersih, 0, 31);

        if ($bersih === '') {
            $bersih = 'Sheet';
        }

        $nama = $bersih;
        $n = 2;
        while (isset($dipakai[mb_strtolower($nama)])) {
            $suffix = ' ('.$n.')';
            $nama = mb_substr($bersih, 0, 31 - mb_strlen($suffix)).$suffix;
            $n++;
        }

        $dipakai[mb_strtolower($nama)] = true;

        return $nama;
    }
}
