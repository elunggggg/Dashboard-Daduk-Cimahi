<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar "bagian" (section grafik/tabel) di halaman publik Demografi, Sosial,
 * dan Mobilitas — supaya Petugas bisa menampilkan/menyembunyikannya satu per
 * satu tanpa menyentuh kode. Barisnya didaftarkan otomatis oleh komponen
 * <x-seksi> saat halaman pertama kali dirender (firstOrCreate), jadi tabel ini
 * mengikuti apa pun yang ada di Blade tanpa perlu seeder yang harus disamakan
 * manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seksi_dashboard', function (Blueprint $table) {
            $table->id();
            $table->string('halaman', 30);          // demografi | sosial | mobilitas
            $table->string('kunci', 60);            // slug unik dalam satu halaman
            $table->string('judul', 150);          // teks yang tampil di UI kelola
            $table->boolean('tampil')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['halaman', 'kunci']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seksi_dashboard');
    }
};
