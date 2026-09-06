<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom opsional untuk orientasi 'kota': saat diisi, kolom data dicari lewat
 * teks header (sama seperti mekanisme header bertingkat di orientasi 'baris'),
 * bukan lewat offset_kolom tetap. Diperlukan karena posisi kolom "Bukan
 * Angkatan Kerja"/"Angkatan Kerja"/dsb pada sheet AngkatanKerjaPendidikan
 * berbeda antara berkas Semester I dan Semester II (S1 punya kolom rincian
 * L/P tambahan yang menggeser posisi kolom totalnya), sedangkan teks
 * headernya sendiri sama persis di kedua berkas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konfigurasi_import', function (Blueprint $table) {
            $table->string('teks_header_kolom')->nullable()->after('offset_kolom');
        });
    }

    public function down(): void
    {
        Schema::table('konfigurasi_import', function (Blueprint $table) {
            $table->dropColumn('teks_header_kolom');
        });
    }
};
