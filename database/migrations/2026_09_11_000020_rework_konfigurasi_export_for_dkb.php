<?php

use App\Models\KonfigurasiExport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi Export (kini "Konfigurasi Unduh") disesuaikan dengan berkas Excel
 * gaya DKB: baris konfigurasi tidak lagi 9 "kolom flat" (Kode Wilayah,
 * Kecamatan, …) melainkan ELEMEN STRUKTUR berkas DKB — No, Wilayah, sub-kolom
 * L/P/Jumlah, kolom "Jumlah Seluruhnya", baris subtotal kecamatan, baris total
 * Kota. Kolom `format` (teks/angka) tak relevan lagi → di-drop.
 *
 * Baris lama dihapus; KonfigurasiExportSeeder mengisi baris baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        KonfigurasiExport::query()->delete();

        Schema::table('konfigurasi_export', function (Blueprint $table) {
            $table->dropColumn('format');
        });

        (new \Database\Seeders\KonfigurasiExportSeeder)->run();
    }

    public function down(): void
    {
        KonfigurasiExport::query()->delete();

        Schema::table('konfigurasi_export', function (Blueprint $table) {
            $table->enum('format', ['teks', 'angka'])->default('teks')->after('aktif');
        });
    }
};
