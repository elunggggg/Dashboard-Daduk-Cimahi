<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama sistem & nama instansi yang bisa diubah Petugas lewat menu
 * "Tampilan Sistem" — dipakai di navbar, sidebar, judul tab, footer,
 * halaman login, dan masthead Dashboard Publik. Kolom nullable: kalau
 * kosong, aplikasi jatuh ke teks bawaan ("DADUK" / "Disdukcapil Kota Cimahi").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->string('nama_sistem', 60)->nullable()->after('id');
            $table->string('nama_instansi', 120)->nullable()->after('nama_sistem');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->dropColumn(['nama_sistem', 'nama_instansi']);
        });
    }
};
