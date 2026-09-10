<?php

namespace Database\Seeders;

use App\Models\KonfigurasiExport;
use Illuminate\Database\Seeder;

/**
 * Isi bawaan Konfigurasi Export = kolom berkas ekspor persis seperti sebelum
 * fitur ini ada (Kode Wilayah · Kecamatan · Kelurahan · Periode · Indikator ·
 * Kategori · Laki-laki · Perempuan · Jumlah).
 *
 * Idempoten: `updateOrCreate` pada `kunci` menjaga urutan/format bawaan tetap
 * konsisten bila di-seed ulang, TANPA menimpa `label`/`aktif` yang mungkin
 * sudah diubah Petugas — kecuali baris belum ada (baru dibuat penuh dari SUMBER).
 */
class KonfigurasiExportSeeder extends Seeder
{
    public function run(): void
    {
        $urutan = 0;

        foreach (KonfigurasiExport::SUMBER as $kunci => $meta) {
            $urutan += 10;

            $baris = KonfigurasiExport::firstOrNew(['kunci' => $kunci]);

            // Kolom teknis selalu diselaraskan ke bawaan; label & aktif hanya
            // diisi saat baris memang baru (jangan timpa penyesuaian Petugas).
            $baris->format = $meta['format'];

            if (! $baris->exists) {
                $baris->label  = $meta['label'];
                $baris->urutan = $urutan;
                $baris->aktif  = true;
            }

            $baris->save();
        }
    }
}
