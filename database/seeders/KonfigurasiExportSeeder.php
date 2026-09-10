<?php

namespace Database\Seeders;

use App\Models\KonfigurasiExport;
use Illuminate\Database\Seeder;

/**
 * Isi bawaan Konfigurasi Unduh = elemen berkas Excel gaya DKB (No, Wilayah,
 * Laki-laki, Perempuan, Jumlah, Jumlah Seluruhnya, Subtotal per Kecamatan,
 * KOTA CIMAHI) — semua aktif.
 *
 * Idempoten: `firstOrNew` per `kunci`. Label & status aktif hanya diisi saat
 * baris memang baru (jangan menimpa penyesuaian Petugas); `urutan` selalu
 * diselaraskan ke bawaan.
 */
class KonfigurasiExportSeeder extends Seeder
{
    public function run(): void
    {
        $urutan = 0;

        foreach (KonfigurasiExport::SUMBER as $kunci => $meta) {
            $urutan += 10;

            $baris = KonfigurasiExport::firstOrNew(['kunci' => $kunci]);
            $baris->urutan = $urutan;

            if (! $baris->exists) {
                $baris->label = $meta['label'];
                $baris->aktif = true;
            }

            $baris->save();
        }
    }
}
