<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kamus indikator — definisi/satuan/sumber tiap indikator (Modul Metadata, KF-29..32). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metadatas', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_indikator', 50)->unique();
            $table->string('nama', 100);
            $table->string('modul', 50);
            $table->text('definisi');
            $table->string('satuan', 50)->default('Jiwa');
            $table->string('sumber', 100)->default('Disdukcapil Kota Cimahi');
            $table->string('periode_update', 30)->default('Per Semester');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadatas');
    }
};
