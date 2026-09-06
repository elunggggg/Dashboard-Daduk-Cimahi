<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Orientasi 'kecamatan' — untuk sheet yang datanya per-KECAMATAN (3 baris),
 * bukan per-kelurahan (15 baris) atau se-Kota (1 baris). Dipakai pertama kali
 * untuk sheet Perkawinan_KU (Tabel 17): 4 blok status perkawinan bersusun
 * vertikal, tiap blok punya 3 baris kecamatan × 16 kolom kelompok umur.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok', 'kota', 'kolom_urut', 'kecamatan') NOT NULL DEFAULT 'baris'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok', 'kota', 'kolom_urut') NOT NULL DEFAULT 'baris'");
    }
};
