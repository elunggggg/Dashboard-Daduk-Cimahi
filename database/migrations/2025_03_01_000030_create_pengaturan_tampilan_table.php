<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pengaturan tampilan Dashboard Publik yang bisa diubah Petugas (KF-49, UC-32..34) — baris tunggal. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_tampilan', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('logo_instansi_path')->nullable();
            $table->string('latar_belakang_path')->nullable();
            $table->string('latar_belakang_body_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_tampilan');
    }
};
