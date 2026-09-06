<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sebagian sheet DKB (Pekerjaan, KelompokKerja, TerbitAktaKawin, dst) HANYA
 * berisi rekap se-KOTA tanpa rincian kelurahan — tidak seperti mayoritas
 * sheet lain yang selalu punya 15 baris kelurahan. Kolom ini menandai SATU
 * baris khusus "Kota Cimahi" di dim_wilayah untuk menampung data semacam
 * itu, supaya tetap bisa masuk star schema (wilayah_id tetap wajib di
 * data_agregat) tanpa mengklaim datanya per kelurahan.
 *
 * PENTING: baris ini SENGAJA tidak dikenali oleh
 * DimWilayah::kamusPencocokan() (dipakai SEMUA sheet per-kelurahan) — kalau
 * "KOTA CIMAHI" ikut masuk kamus itu, baris total kota yang muncul di
 * BANYAK sheet per-kelurahan (sebagai baris rekap di bawah 15 kelurahan)
 * akan ikut kebaca dan mencemari total. Sheet kota-only pakai jalur
 * pembacaan TERPISAH (ORIENTASI_KOTA) yang mencari wilayah ini secara
 * eksplisit lewat kolom is_kota, bukan lewat kamus umum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_wilayah', function (Blueprint $table) {
            $table->boolean('is_kota')->default(false)->after('nama_kecamatan');
        });

        DB::table('dim_wilayah')->insert([
            'kode_kemendagri' => '32.77',
            'kode_kecamatan'  => null,
            'nama_kelurahan'  => 'Kota Cimahi',
            'nama_kecamatan'  => 'Kota Cimahi',
            'luas_km2'        => null,
            'is_kota'         => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('dim_wilayah')->where('is_kota', true)->delete();

        Schema::table('dim_wilayah', function (Blueprint $table) {
            $table->dropColumn('is_kota');
        });
    }
};
