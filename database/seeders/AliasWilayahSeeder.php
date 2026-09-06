<?php

namespace Database\Seeders;

use App\Models\AliasWilayah;
use App\Models\DimWilayah;
use Illuminate\Database\Seeder;

class AliasWilayahSeeder extends Seeder
{
    /**
     * Variasi penulisan nama kelurahan yang mungkin muncul di file Excel.
     *
     * Kunci = nama kelurahan resmi di dim_wilayah.
     * Nilai = daftar ejaan lain yang harus dianggap sama.
     *
     * Daftar ini sengaja dibuat longgar (memuat kemungkinan yang belum tentu
     * terjadi). Alias yang tidak pernah terpakai tidak merugikan apa pun,
     * sedangkan alias yang kurang bikin satu kelurahan hilang dari hasil import
     * dan itu baru ketahuan setelah angkanya salah.
     *
     * Kalau nanti ada ejaan baru yang belum tercakup, tambahkan lewat halaman
     * Kelola Wilayah — tidak perlu mengubah file ini.
     */
    private array $alias = [
        'Karangmekar'    => ['KARANG MEKAR', 'KRG MEKAR', 'KARANGMEKAR'],
        'Cigugur Tengah' => ['CIGUGUR TGH', 'CIGUGURTENGAH', 'CIGUGUR-TENGAH'],
        'Cibeureum'      => ['CIBEUREM', 'CIBERUEUM'],
        'Leuwigajah'     => ['LEUWI GAJAH', 'LEUWIGAJAH'],
        'Pasirkaliki'    => ['PASIR KALIKI'],
        'Citeureup'      => ['CITEUREUP', 'CITERUEP'],
        'Cipageran'      => ['CIPAGERAN'],
        'Setiamanah'     => ['SETIA MANAH'],
        'Padasuka'       => ['PADA SUKA'],
        'Cibabat'        => ['CIBABAT'],
        'Melong'         => ['MELONG'],
        'Utama'          => ['UTAMA'],
        'Cibeber'        => ['CIBEBER'],
        'Baros'          => ['BAROS'],

        // "CIMAHI" sengaja TIDAK diberi alias tambahan. Nama kelurahannya sama
        // persis dengan nama kotanya, sehingga alias longgar seperti
        // "KOTA CIMAHI" justru berisiko membuat baris TOTAL KOTA ikut terbaca
        // sebagai data satu kelurahan — angka kota akan tercatat dua kali.
    ];

    public function run(): void
    {
        $wilayah = DimWilayah::pluck('id', 'nama_kelurahan');

        foreach ($this->alias as $namaKelurahan => $daftar) {
            $wilayahId = $wilayah[$namaKelurahan] ?? null;

            // Kelurahan belum ada berarti DimWilayahSeeder belum jalan.
            // Dilewati saja, bukan error — seeder harus tetap idempoten.
            if (! $wilayahId) {
                continue;
            }

            foreach ($daftar as $nama) {
                AliasWilayah::updateOrCreate(
                    ['nama_alias' => AliasWilayah::normalkan($nama)],
                    ['wilayah_id' => $wilayahId],
                );
            }
        }
    }
}
