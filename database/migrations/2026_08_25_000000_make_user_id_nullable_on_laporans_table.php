<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ekspor kini bisa dipakai Publik tanpa login (lihat DashboardPublikController),
 * jadi baris riwayat ekspor Publik tidak punya user_id. Tidak pakai
 * Blueprint::change() (butuh doctrine/dbal yang tidak terpasang) — drop FK,
 * ubah kolom lewat SQL mentah, lalu pasang ulang FK dengan nullOnDelete().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE laporans MODIFY user_id BIGINT UNSIGNED NULL');

        Schema::table('laporans', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement('ALTER TABLE laporans MODIFY user_id BIGINT UNSIGNED NOT NULL');

        Schema::table('laporans', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
