<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi Export — mengatur BAGAIMANA berkas ekspor data agregat ditulis:
 * kolom mana yang ikut, urutannya, label header-nya, dan format angkanya.
 *
 * Menggantikan fitur "Konfigurasi Import" lama yang salah konsep (itu mengatur
 * cara MEMBACA Excel DKB; kebutuhan sebenarnya adalah mengatur cara MENULIS
 * berkas ekspor). Mesin import DKB (tabel konfigurasi_import) tidak tersentuh.
 *
 * Kumpulan `kunci` bersifat TETAP (sumber kolom di data_agregat sudah baku) —
 * Petugas mengaktifkan/menonaktifkan, mengurutkan, dan mengganti label saja,
 * bukan menambah kolom sembarang. Baris awal diisi KonfigurasiExportSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi_export', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 40)->unique();          // kode_wilayah, kecamatan, ... (lihat model)
            $table->string('label', 100);                    // teks header di berkas ekspor
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->enum('format', ['teks', 'angka'])->default('teks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_export');
    }
};
