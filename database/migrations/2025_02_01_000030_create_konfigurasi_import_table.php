<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peta kolom/baris berkas Excel DKB Disdukcapil ke kategori data_agregat —
 * mesin di balik import fleksibel (KF-44/45, KNF-15/16/20/21). Dikelola lewat
 * seeder (developer), bukan lewat UI Petugas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi_import', function (Blueprint $table) {
            $table->id();
            $table->string('nama_profil', 100);
            $table->string('nama_sheet', 100);
            $table->string('jenis_indikator', 50);
            $table->string('label', 100);
            $table->string('teks_header', 150);
            $table->unsignedTinyInteger('offset_kolom')->default(0);
            $table->enum('orientasi', ['baris', 'kolom'])->default('baris');
            $table->string('teks_header_wilayah', 50)->default('Wilayah');
            $table->unsignedTinyInteger('baris_maks_pencarian_header')->default(12);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_import');
    }
};
