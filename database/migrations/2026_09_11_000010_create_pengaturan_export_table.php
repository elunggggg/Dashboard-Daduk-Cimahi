<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan format berkas ekspor — satu baris (pola sama dgn
 * pengaturan_tampilan). Menampung nilai yang tadinya hardcoded di
 * EksporController / ekspor/pdf.blade.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_export', function (Blueprint $table) {
            $table->id();
            $table->enum('format_bawaan', ['excel', 'pdf'])->default('excel');
            $table->unsignedInteger('batas_baris_pdf')->default(3000);
            $table->enum('orientasi_pdf', ['potrait', 'landscape'])->default('landscape');
            $table->string('kop_judul', 150)->nullable();
            $table->string('kop_subjudul', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_export');
    }
};
