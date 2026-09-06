<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Tambah nilai 'kolom_urut' ke enum orientasi — lihat KonfigurasiImport::ORIENTASI_KOLOM_URUT. */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok', 'kota', 'kolom_urut') NOT NULL DEFAULT 'baris'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE konfigurasi_import MODIFY orientasi ENUM('baris', 'kolom', 'blok', 'kota') NOT NULL DEFAULT 'baris'");
    }
};
