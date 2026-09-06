<?php

namespace App\Exports;

use App\Imports\DataAgregatImport;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Template untuk import Mode B (format flat sederhana).
 *
 * Dua varian, sesuai dua tombol di halaman import:
 * - KOSONG: hanya baris header. Dipakai kalau Petugas sudah paham formatnya.
 * - CONTOH: header + beberapa baris terisi yang diambil dari data referensi
 *   yang BENAR-BENAR ada di database. Contoh yang dikarang bisa memuat kode
 *   wilayah atau label yang tidak terdaftar, dan Petugas baru sadar setelah
 *   importnya ditolak.
 */
class TemplateImportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly bool $denganContoh = true)
    {
    }

    public function headings(): array
    {
        return DataAgregatImport::KOLOM_WAJIB;
    }

    public function array(): array
    {
        if (! $this->denganContoh) {
            return [];
        }

        $wilayah  = DimWilayah::orderBy('kode_kemendagri')->first();
        $waktu    = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->first();
        $kategori = DimKategori::where('jenis_indikator', 'jenis_kelamin')->orderBy('label')->get();

        if (! $wilayah || ! $waktu || $kategori->isEmpty()) {
            return [];
        }

        return $kategori->map(fn (DimKategori $k) => [
            $wilayah->kode_kemendagri,
            $waktu->tahun,
            $waktu->semester,
            $k->jenis_indikator,
            $k->label,
            0,
        ])->all();
    }

    public function title(): string
    {
        return $this->denganContoh ? 'Contoh Terisi' : 'Template Data Agregat';
    }
}
