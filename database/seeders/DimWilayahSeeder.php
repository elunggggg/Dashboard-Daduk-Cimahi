<?php

namespace Database\Seeders;

use App\Models\DimWilayah;
use Illuminate\Database\Seeder;

class DimWilayahSeeder extends Seeder
{
    public function run(): void
    {
        // 15 kelurahan Kota Cimahi (3 kecamatan).
        //
        // Kode Kemendagri di bawah adalah kode TERVERIFIKASI, menggantikan kode
        // placeholder berurutan yang dipakai versi sebelumnya. Urutannya memang
        // berbeda dari dugaan awal — mis. 32.77.01.1001 milik MELONG, bukan
        // CIBEBER. Jangan "merapikan" urutannya secara alfabetis.
        //
        // luas_km2 masih angka perkiraan (total ±40,3 km²) dan menempel pada
        // NAMA kelurahan, bukan pada kodenya.
        $data = [
            // Kecamatan Cimahi Selatan (5)
            ['kode_kemendagri' => '32.77.01.1001', 'nama_kelurahan' => 'Melong',         'nama_kecamatan' => 'Cimahi Selatan', 'luas_km2' => 2.86],
            ['kode_kemendagri' => '32.77.01.1002', 'nama_kelurahan' => 'Cibeureum',      'nama_kecamatan' => 'Cimahi Selatan', 'luas_km2' => 2.75],
            ['kode_kemendagri' => '32.77.01.1003', 'nama_kelurahan' => 'Utama',          'nama_kecamatan' => 'Cimahi Selatan', 'luas_km2' => 2.67],
            ['kode_kemendagri' => '32.77.01.1004', 'nama_kelurahan' => 'Leuwigajah',     'nama_kecamatan' => 'Cimahi Selatan', 'luas_km2' => 5.10],
            ['kode_kemendagri' => '32.77.01.1005', 'nama_kelurahan' => 'Cibeber',        'nama_kecamatan' => 'Cimahi Selatan', 'luas_km2' => 3.52],
            // Kecamatan Cimahi Tengah (6)
            ['kode_kemendagri' => '32.77.02.1001', 'nama_kelurahan' => 'Baros',          'nama_kecamatan' => 'Cimahi Tengah',  'luas_km2' => 1.68],
            ['kode_kemendagri' => '32.77.02.1002', 'nama_kelurahan' => 'Cigugur Tengah', 'nama_kecamatan' => 'Cimahi Tengah',  'luas_km2' => 2.15],
            ['kode_kemendagri' => '32.77.02.1003', 'nama_kelurahan' => 'Karangmekar',    'nama_kecamatan' => 'Cimahi Tengah',  'luas_km2' => 1.18],
            ['kode_kemendagri' => '32.77.02.1004', 'nama_kelurahan' => 'Setiamanah',     'nama_kecamatan' => 'Cimahi Tengah',  'luas_km2' => 1.53],
            ['kode_kemendagri' => '32.77.02.1005', 'nama_kelurahan' => 'Padasuka',       'nama_kecamatan' => 'Cimahi Tengah',  'luas_km2' => 2.32],
            ['kode_kemendagri' => '32.77.02.1006', 'nama_kelurahan' => 'Cimahi',         'nama_kecamatan' => 'Cimahi Tengah',  'luas_km2' => 1.24],
            // Kecamatan Cimahi Utara (4)
            ['kode_kemendagri' => '32.77.03.1001', 'nama_kelurahan' => 'Pasirkaliki',    'nama_kecamatan' => 'Cimahi Utara',   'luas_km2' => 3.18],
            ['kode_kemendagri' => '32.77.03.1002', 'nama_kelurahan' => 'Cibabat',        'nama_kecamatan' => 'Cimahi Utara',   'luas_km2' => 2.81],
            ['kode_kemendagri' => '32.77.03.1003', 'nama_kelurahan' => 'Citeureup',      'nama_kecamatan' => 'Cimahi Utara',   'luas_km2' => 2.74],
            ['kode_kemendagri' => '32.77.03.1004', 'nama_kelurahan' => 'Cipageran',      'nama_kecamatan' => 'Cimahi Utara',   'luas_km2' => 4.57],
        ];

        // updateOrCreate agar seeder aman dijalankan ulang di database yang sudah terisi
        foreach ($data as $row) {
            // Kode kecamatan selalu tiga segmen pertama dari kode kelurahan,
            // diturunkan di sini supaya tidak ada peluang salah ketik manual.
            $row['kode_kecamatan'] = implode('.', array_slice(explode('.', $row['kode_kemendagri']), 0, 3));

            DimWilayah::updateOrCreate(
                ['kode_kemendagri' => $row['kode_kemendagri']],
                $row,
            );
        }
    }
}
