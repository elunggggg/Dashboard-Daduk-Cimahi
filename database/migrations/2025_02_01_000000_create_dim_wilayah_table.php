<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Master kecamatan/kelurahan Kota Cimahi — dimensi wilayah star schema. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dim_wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kemendagri', 20)->unique();
            $table->string('kode_kecamatan', 15)->nullable()->index();
            $table->string('nama_kelurahan', 100);
            $table->string('nama_kecamatan', 100);
            $table->decimal('luas_km2', 8, 2)->nullable();
            $table->timestamps();
        });

        // Ejaan alternatif nama kelurahan yang mungkin muncul di berkas Excel
        // Disdukcapil (mis. "KARANG MEKAR" untuk "Karangmekar") — dipakai saat
        // import DKB supaya baris tidak terlewat hanya karena beda ejaan.
        Schema::create('alias_wilayah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wilayah_id')->constrained('dim_wilayah')->cascadeOnDelete();
            $table->string('nama_alias', 100);
            $table->timestamps();
            $table->unique('nama_alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alias_wilayah');
        Schema::dropIfExists('dim_wilayah');
    }
};
