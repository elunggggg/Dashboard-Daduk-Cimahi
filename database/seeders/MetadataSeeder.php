<?php

namespace Database\Seeders;

use App\Models\Metadata;
use Illuminate\Database\Seeder;

/**
 * Mengisi tabel metadatas dari kamus indikator bawaan aplikasi
 * (dulu App\Http\Controllers\MetadataController::KAMUS, sekarang dipindah
 * ke DB supaya Petugas bisa mengubahnya lewat Kelola Metadata — KF-32).
 * Idempoten: aman dijalankan ulang, tidak menimpa perubahan Petugas yang
 * sudah tersimpan.
 */
class MetadataSeeder extends Seeder
{
    private const KAMUS = [
        'jenis_kelamin' => [
            'nama'     => 'Jenis Kelamin',
            'definisi' => 'Pengelompokan penduduk berdasarkan jenis kelamin biologis (laki-laki dan perempuan).',
            'modul'    => 'Demografi',
        ],
        'kelompok_umur' => [
            'nama'     => 'Kelompok Umur',
            'definisi' => 'Pengelompokan penduduk berdasarkan rentang usia dalam kelipatan 5 tahun.',
            'modul'    => 'Demografi',
        ],
        'status_kawin' => [
            'nama'     => 'Status Perkawinan',
            'definisi' => 'Status perkawinan penduduk usia 15 tahun ke atas: belum kawin, kawin, cerai hidup, atau cerai mati.',
            'modul'    => 'Demografi',
        ],
        'disabilitas' => [
            'nama'     => 'Disabilitas',
            'definisi' => 'Kondisi keterbatasan fisik, mental, atau sensorik yang berdampak jangka panjang pada aktivitas kehidupan sehari-hari.',
            'modul'    => 'Demografi',
        ],
        'golongan_darah' => [
            'nama'     => 'Golongan Darah',
            'definisi' => 'Golongan darah penduduk sebagaimana tercatat pada basis data kependudukan (A, B, AB, O, atau belum diketahui). Rincian rhesus belum dipetakan, sehingga totalnya sedikit di bawah jumlah penduduk.',
            'modul'    => 'Demografi',
        ],
        'pendidikan' => [
            'nama'     => 'Pendidikan',
            'definisi' => 'Jenjang pendidikan formal tertinggi yang pernah atau sedang ditempuh oleh penduduk.',
            'modul'    => 'Sosial',
        ],
        'pekerjaan' => [
            'nama'     => 'Pekerjaan',
            'definisi' => 'Jenis pekerjaan utama atau status kegiatan ekonomi penduduk.',
            'modul'    => 'Sosial',
        ],
        'agama' => [
            'nama'     => 'Agama',
            'definisi' => 'Agama atau kepercayaan yang dianut penduduk sesuai yang tercatat dalam dokumen kependudukan.',
            'modul'    => 'Sosial',
        ],
        'kepemilikan_ktp' => [
            'nama'     => 'Kepemilikan KTP-el',
            'definisi' => 'Status kepemilikan Kartu Tanda Penduduk elektronik bagi penduduk wajib KTP (≥17 tahun atau sudah menikah).',
            'modul'    => 'Sosial',
        ],
        'kepemilikan_kk' => [
            'nama'     => 'Kepemilikan KK',
            'definisi' => 'Status kepemilikan Kartu Keluarga (KK) sebagai dokumen resmi yang mencatat data keluarga.',
            'modul'    => 'Sosial',
        ],
        'kepemilikan_kia' => [
            'nama'     => 'Kepemilikan KIA',
            'definisi' => 'Status kepemilikan Kartu Identitas Anak bagi penduduk di bawah 17 tahun.',
            'modul'    => 'Sosial',
        ],
        'akta_lahir' => [
            'nama'     => 'Akta Kelahiran',
            'definisi' => 'Status kepemilikan Akta Kelahiran sebagai dokumen resmi pencatatan kelahiran.',
            'modul'    => 'Sosial',
        ],
        'akta_kawin' => [
            'nama'     => 'Akta Perkawinan',
            'definisi' => 'Status kepemilikan Akta Perkawinan bagi penduduk yang berstatus kawin.',
            'modul'    => 'Sosial',
        ],
        'akta_cerai' => [
            'nama'     => 'Akta Perceraian',
            'definisi' => 'Status kepemilikan Akta Perceraian bagi penduduk yang berstatus cerai hidup.',
            'modul'    => 'Sosial',
        ],
        'mobilitas_datang' => [
            'nama'     => 'Mobilitas — Pendatang',
            'definisi' => 'Jumlah penduduk yang berpindah masuk (datang) ke Kota Cimahi dalam satu semester. DKB hanya memberi satu angka total per kelurahan, tanpa rincian asal.',
            'modul'    => 'Mobilitas',
        ],
        'mobilitas_pindah' => [
            'nama'     => 'Mobilitas — Pindah Keluar',
            'definisi' => 'Jumlah penduduk yang pindah keluar dari Kota Cimahi dalam satu semester. DKB hanya memberi satu angka total per kelurahan, tanpa rincian tujuan.',
            'modul'    => 'Mobilitas',
        ],
    ];

    public function run(): void
    {
        foreach (self::KAMUS as $kode => $meta) {
            Metadata::firstOrCreate(
                ['jenis_indikator' => $kode],
                [
                    'nama'           => $meta['nama'],
                    'modul'          => $meta['modul'],
                    'definisi'       => $meta['definisi'],
                    'satuan'         => 'jiwa',
                    'sumber'         => 'Disdukcapil Kota Cimahi',
                    'periode_update' => 'Per Semester',
                ]
            );
        }
    }
}
