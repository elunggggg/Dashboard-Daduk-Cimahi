<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Tambah nilai 'kota' ke enum orientasi — lihat KonfigurasiImport::ORIENTASI_KOTA. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok', 'kota') NOT NULL DEFAULT 'baris'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok') NOT NULL DEFAULT 'baris'");
    }
};
