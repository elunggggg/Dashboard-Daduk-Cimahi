<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BackupController::store() selalu menulis 'status' dan kadang 'catatan'
 * (pesan galat) — tapi keduanya belum pernah ada di skema, jadi terbuang diam-diam
 * oleh mass-assignment guard ($fillable Backup model tidak memuatnya). Akibatnya
 * "Riwayat Percobaan" di halaman Backup selalu menampilkan badge status kosong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->after('ukuran_bytes');
            $table->text('catatan')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn(['status', 'catatan']);
        });
    }
};
