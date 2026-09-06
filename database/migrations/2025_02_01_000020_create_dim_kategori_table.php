<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dimensi kategori indikator (jenis_indikator + label), mis.
 * jenis_indikator=jenis_kelamin, label=Laki-laki. KNF-31: indikator baru
 * cukup baris baru di sini, tidak perlu ALTER TABLE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dim_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_indikator', 50);
            $table->string('label', 100);
            $table->timestamps();
            $table->unique(['jenis_indikator', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dim_kategori');
    }
};
