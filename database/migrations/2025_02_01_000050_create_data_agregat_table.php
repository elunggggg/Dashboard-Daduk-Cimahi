<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fact table star schema — satu baris = satu angka agregat (wilayah × waktu ×
 * kategori). Sengaja TIDAK menyimpan data granular per-NIK (Batasan Masalah SRG).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_agregat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wilayah_id')->constrained('dim_wilayah')->cascadeOnDelete();
            $table->foreignId('waktu_id')->constrained('dim_waktu')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('dim_kategori')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah')->default(0);
            $table->foreignId('import_id')->nullable()->constrained('import_excels')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // KNF-18: cegah data ganda pada wilayah+periode+kategori yang sama
            $table->unique(['wilayah_id', 'waktu_id', 'kategori_id'], 'data_agregat_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_agregat');
    }
};
