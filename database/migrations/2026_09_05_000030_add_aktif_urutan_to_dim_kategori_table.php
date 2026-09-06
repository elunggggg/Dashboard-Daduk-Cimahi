<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KF Kelola Indikator: Petugas bisa menonaktifkan indikator supaya tidak
 * tampil di dashboard publik (soft delete lewat flag, bukan hapus baris —
 * baris data_agregat yang merujuknya tetap ada) dan mengatur urutan tampil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_kategori', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('label');
            $table->unsignedInteger('urutan')->default(0)->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('dim_kategori', function (Blueprint $table) {
            $table->dropColumn(['aktif', 'urutan']);
        });
    }
};
