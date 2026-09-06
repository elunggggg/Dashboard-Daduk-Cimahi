<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Beberapa sheet DKB menyimpan tabel rekap/pivot sisa proses edit manual di
 * baris-baris ATAS sebelum tabel utama (mis. SHBKEL) yang secara kebetulan
 * memuat teks yang sama persis dengan header tabel utama ("WILAYAH",
 * "KEPALA KELUARGA"). Karena pencarian header selalu menyusuri dari baris
 * pertama, kecocokan sampah itu ketemu lebih dulu dan salah mendarat.
 *
 * Kolom ini memberi titik AWAL pencarian (selain batas akhir yang sudah ada
 * di baris_maks_pencarian_header), supaya baris-baris sampah di atas tabel
 * asli bisa dilompati secara eksplisit per sheet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konfigurasi_import', function (Blueprint $table) {
            $table->unsignedTinyInteger('baris_mulai_pencarian_header')->default(0)->after('baris_maks_pencarian_header');
        });
    }

    public function down(): void
    {
        Schema::table('konfigurasi_import', function (Blueprint $table) {
            $table->dropColumn('baris_mulai_pencarian_header');
        });
    }
};
