<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah nilai 'blok' ke enum orientasi — dipakai sheet AngkatanKerja yang
 * nama wilayahnya cuma ada di baris pertama tiap kelompok baris (lihat
 * KonfigurasiImport::ORIENTASI_BLOK). Laravel Schema Builder tidak punya
 * cara mengubah nilai ENUM, jadi ALTER TABLE ditulis manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok') NOT NULL DEFAULT 'baris'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom') NOT NULL DEFAULT 'baris'");
    }
};
