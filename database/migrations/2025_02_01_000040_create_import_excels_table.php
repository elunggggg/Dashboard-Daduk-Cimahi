<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Riwayat unggahan import Excel Petugas (KF-44/46, KNF-22/23, UC-29..31). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_excels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_file');
            $table->string('path_file')->nullable();
            $table->enum('mode', ['dkb', 'template'])->default('template');
            $table->string('nama_profil', 100)->nullable();
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->enum('status', ['menunggu', 'pratinjau', 'berhasil', 'gagal', 'dibatalkan'])->default('menunggu');
            $table->unsignedInteger('jumlah_baris')->nullable();
            $table->text('pesan_error')->nullable();
            $table->text('catatan_hasil')->nullable();
            $table->timestamp('data_dihapus_pada')->nullable();
            $table->foreignId('dihapus_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('jumlah_baris_dihapus')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_excels');
    }
};
