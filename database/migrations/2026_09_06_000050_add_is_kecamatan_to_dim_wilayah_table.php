<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sama seperti is_kota (lihat migrasi 2026_09_06_000000), tapi untuk sheet
 * yang datanya per-KECAMATAN (3 baris: Cimahi Selatan/Tengah/Utara) —
 * bukan per-kelurahan (15 baris) atau se-Kota (1 baris). Dipakai pertama
 * kali untuk sheet Perkawinan_KU (Tabel 17).
 *
 * PENTING: ketiga baris ini SENGAJA tidak dikenali oleh
 * DimWilayah::kamusPencocokan() — alasan yang sama persis dengan is_kota:
 * nama kecamatan sering muncul sebagai baris subtotal di sheet per-kelurahan
 * dan tidak boleh ikut kebaca sebagai "kelurahan ke-16/17/18".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dim_wilayah', function (Blueprint $table) {
            $table->boolean('is_kecamatan')->default(false)->after('is_kota');
        });

        $kecamatan = [
            ['nama' => 'Cimahi Selatan', 'kode' => '32.77.01'],
            ['nama' => 'Cimahi Tengah', 'kode' => '32.77.02'],
            ['nama' => 'Cimahi Utara', 'kode' => '32.77.03'],
        ];

        foreach ($kecamatan as $k) {
            DB::table('dim_wilayah')->insert([
                'kode_kemendagri' => $k['kode'],
                'kode_kecamatan'  => $k['kode'],
                'nama_kelurahan'  => $k['nama'],
                'nama_kecamatan'  => $k['nama'],
                'luas_km2'        => null,
                'is_kota'         => false,
                'is_kecamatan'    => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('dim_wilayah')->where('is_kecamatan', true)->delete();

        Schema::table('dim_wilayah', function (Blueprint $table) {
            $table->dropColumn('is_kecamatan');
        });
    }
};
