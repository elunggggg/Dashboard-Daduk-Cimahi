<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `lebar` menentukan berapa kolom yang ditempati satu bagian pada grid cair
 * (auto-fit) halaman publik: sepertiga / separuh / penuh. Dipakai supaya saat
 * ada bagian yang disembunyikan, sisa bagian mengalir mengisi ruang tanpa
 * meninggalkan celah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seksi_dashboard', function (Blueprint $table) {
            $table->string('lebar', 12)->default('sepertiga')->after('tampil');
        });
    }

    public function down(): void
    {
        Schema::table('seksi_dashboard', function (Blueprint $table) {
            $table->dropColumn('lebar');
        });
    }
};
