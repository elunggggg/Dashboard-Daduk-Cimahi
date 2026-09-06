<?php

namespace Database\Seeders;

use App\Models\DimKategori;
use Illuminate\Database\Seeder;

class DimKategoriSeeder extends Seeder
{
    /**
     * Katalog ini WAJIB sama persis dengan label yang dihasilkan importer DKB
     * (lihat KonfigurasiImportSeeder). Keduanya bertemu di data_agregat lewat
     * pasangan jenis_indikator + label, jadi satu huruf beda saja membuat
     * kartu di dashboard menampilkan 0 padahal angkanya ada di database.
     *
     * Versi sebelumnya berisi label karangan era data dummy — 'Memiliki KK',
     * 'Sudah Rekam/Cetak', 'Belum Tercatat', 'Dalam Kota' — yang tidak pernah
     * muncul di berkas Disdukcapil mana pun. Label-label itu sudah dibuang.
     *
     * Sumber kebenarannya sekarang berkas DKB, bukan tebakan aplikasi.
     */
    private array $katalog = [
        // ── Demografi ────────────────────────────────────────────────────────
        'jenis_kelamin'   => ['Laki-laki', 'Perempuan'],

        'kelompok_umur'   => [
            '0-4 Tahun','5-9 Tahun','10-14 Tahun','15-19 Tahun',
            '20-24 Tahun','25-29 Tahun','30-34 Tahun','35-39 Tahun',
            '40-44 Tahun','45-49 Tahun','50-54 Tahun','55-59 Tahun',
            '60-64 Tahun','65-69 Tahun','70-74 Tahun','75+ Tahun',
        ],

        // Sama seperti 'kelompok_umur' (yang menyimpan kolom TOTAL sheet
        // KelompokUmur), tapi untuk kolom Laki-laki/Perempuan-nya — dipakai
        // piramida penduduk & donut Anak/Lansia (L/P) di Demografi. Ditambahkan
        // 2026-09-06, lihat KonfigurasiImportSeeder untuk pemetaan offsetnya.
        'kelompok_umur_l' => [
            '0-4 Tahun','5-9 Tahun','10-14 Tahun','15-19 Tahun',
            '20-24 Tahun','25-29 Tahun','30-34 Tahun','35-39 Tahun',
            '40-44 Tahun','45-49 Tahun','50-54 Tahun','55-59 Tahun',
            '60-64 Tahun','65-69 Tahun','70-74 Tahun','75+ Tahun',
        ],
        'kelompok_umur_p' => [
            '0-4 Tahun','5-9 Tahun','10-14 Tahun','15-19 Tahun',
            '20-24 Tahun','25-29 Tahun','30-34 Tahun','35-39 Tahun',
            '40-44 Tahun','45-49 Tahun','50-54 Tahun','55-59 Tahun',
            '60-64 Tahun','65-69 Tahun','70-74 Tahun','75+ Tahun',
        ],
        'status_kawin'    => ['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'],
        'disabilitas'     => [
            'Disabilitas Fisik','Disabilitas Netra/Buta','Disabilitas Rungu/Wicara',
            'Disabilitas Mental/Jiwa','Disabilitas Fisik & Mental','Disabilitas Lainnya',
        ],
        'golongan_darah'  => ['A','B','AB','O','Tidak Tahu'],

        // ── Sosial ───────────────────────────────────────────────────────────
        'pendidikan'      => [
            'Tidak/Belum Sekolah','Belum Tamat SD','Tamat SD/Sederajat',
            'SMP/Sederajat','SMA/Sederajat','Diploma I/II',
            'Diploma III','Diploma IV/S1','S2','S3',
        ],
        'pekerjaan'       => [
            'Aparatur/Pejabat Negara','Tenaga Pengajar','Wiraswasta',
            'Pertanian/Peternakan','Nelayan','Agama dan Kepercayaan',
            'Pelajar/Mahasiswa','Tenaga Kesehatan','Belum/Tidak Bekerja',
            'Pensiunan','Lainnya',
        ],
        'agama'           => ['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Kepercayaan'],

        // Kolom "Jumlah …" ikut disimpan sebagai kategori tersendiri: itulah
        // penyebut resmi dari Disdukcapil untuk menghitung persentase, dan
        // memakainya lebih tepat daripada menjumlah sendiri kolom lain.
        'kepemilikan_ktp' => [
            'Wajib KTP','Sudah Rekam KTP','Belum Rekam KTP','Sudah Cetak KTP','Belum Cetak KTP',
        ],
        'kepemilikan_kk'  => ['KK Sudah TTE','KK Belum TTE','Jumlah Kepala Keluarga'],
        'kepemilikan_kia' => ['Memiliki KIA','Belum Memiliki KIA','Jumlah Anak Usia 0-17 Tahun'],
        'akta_lahir'      => ['Memiliki Akta Lahir','Belum Memiliki Akta Lahir','Jumlah Penduduk'],
        'akta_kawin'      => ['Memiliki Akta Kawin','Belum Memiliki Akta Kawin','Penduduk Status Kawin'],
        'akta_cerai'      => ['Memiliki Akta Cerai','Belum Memiliki Akta Cerai','Penduduk Status Cerai'],

        // ── Mobilitas ────────────────────────────────────────────────────────
        // DKB hanya memberi satu angka total per kelurahan, tanpa rincian asal
        // atau tujuan perpindahan.
        'mobilitas_datang'=> ['Datang'],
        'mobilitas_pindah'=> ['Pindah'],
    ];

    /**
     * firstOrCreate, bukan insert: importer juga membuat kategori sendiri saat
     * menemui label baru, jadi seeder ini harus aman dijalankan ulang di atas
     * database yang sudah berisi data.
     */
    public function run(): void
    {
        foreach ($this->katalog as $jenis => $labels) {
            foreach ($labels as $label) {
                DimKategori::firstOrCreate(
                    ['jenis_indikator' => $jenis, 'label' => $label],
                );
            }
        }
    }
}
