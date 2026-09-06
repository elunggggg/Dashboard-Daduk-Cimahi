<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Disdukcapil kadang mengganti nama sheet antar semester (mis. 'SHBKEL' di
 * berkas Semester II menjadi 'SHBKEL(rev)' di berkas Semester I) walau
 * isinya sama persis. Tanpa alias, Petugas harus mengganti Konfigurasi
 * Import bolak-balik tiap semester tergantung berkas mana yang diunggah.
 *
 * alias_sheet dicoba HANYA bila nama_sheet utama tidak ada di berkas —
 * lihat App\Services\Import\PembacaSheetDkb::baca().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konfigurasi_import', function (Blueprint $table) {
            $table->string('alias_sheet', 100)->nullable()->after('nama_sheet');
        });
    }

    public function down(): void
    {
        Schema::table('konfigurasi_import', function (Blueprint $table) {
            $table->dropColumn('alias_sheet');
        });
    }
};
