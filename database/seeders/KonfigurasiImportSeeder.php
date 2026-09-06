<?php

namespace Database\Seeders;

use App\Models\KonfigurasiImport;
use Illuminate\Database\Seeder;

class KonfigurasiImportSeeder extends Seeder
{
    /**
     * Profil pemetaan "DKB Standar Disdukcapil" untuk 12 sheet.
     *
     * Bentuk tiap entri sheet:
     *   'NamaSheet' => [
     *       'jenis'  => jenis_indikator tujuan di dim_kategori,
     *       'offset' => geser N kolom dari posisi teks header yang ketemu,
     *       'kolom'  => [ label kanonik di aplikasi => teks yang dicari di Excel ],
     *   ]
     *
     * Dua hal yang perlu dipahami:
     *
     * 1. LABEL vs TEKS_HEADER dipisah dengan sengaja. Excel menulis "Katholik",
     *    "Budha", "Khong Hu Chu"; aplikasi memakai ejaan baku "Katolik",
     *    "Buddha", "Konghucu". Yang dicari di file adalah teks_header, yang
     *    disimpan ke dim_kategori adalah label. Jadi ejaan di file boleh
     *    berubah tanpa mengacaukan tampilan dashboard.
     *
     * 2. OFFSET dipakai untuk header bertingkat. Pada sheet berheader grup
     *    (mis. "Pindah" di baris atas, lalu "Laki-laki | Perempuan | Jumlah"
     *    di bawahnya), yang dicari adalah teks grupnya lalu digeser 2 kolom
     *    untuk mendarat di kolom totalnya.
     *
     *    PENTING: nilai offset di bawah diturunkan dari deskripsi sub-kolom di
     *    spesifikasi, BUKAN dari pembacaan langsung file DKB. Sebelum dipakai
     *    produksi, tiap sheet wajib dicek lewat /admin/konfigurasi-import →
     *    "Uji Coba Pemetaan". Kalau angka satu label meleset, hampir pasti
     *    offset-nya yang perlu disetel, bukan kodenya.
     */
    private array $profil = [
        'PendudukJK' => [
            'jenis'  => 'jenis_kelamin',
            'offset' => 0,
            'kolom'  => [
                'Laki-laki' => 'Laki-laki',
                'Perempuan' => 'Perempuan',
            ],
        ],

        /*
         * SATU-SATUNYA sheet bersusun terbalik: baris = kelompok umur,
         * kolom = kelurahan (lima blok tabel bersebelahan, tiap kelurahan
         * memakai tiga kolom L | P | TOTAL — karena itu offsetnya tetap 2).
         *
         * teks_header memakai ejaan berkas ("00-04"), label memakai ejaan yang
         * dipakai urutan umur di DemografiController ("0-4 Tahun").
         */
        'KelompokUmur' => [
            'jenis'     => 'kelompok_umur',
            'offset'    => 2,
            'orientasi' => KonfigurasiImport::ORIENTASI_KOLOM,
            'kolom'     => [
                '0-4 Tahun'   => '00-04',
                '5-9 Tahun'   => '05-09',
                '10-14 Tahun' => '10-14',
                '15-19 Tahun' => '15-19',
                '20-24 Tahun' => '20-24',
                '25-29 Tahun' => '25-29',
                '30-34 Tahun' => '30-34',
                '35-39 Tahun' => '35-39',
                '40-44 Tahun' => '40-44',
                '45-49 Tahun' => '45-49',
                '50-54 Tahun' => '50-54',
                '55-59 Tahun' => '55-59',
                '60-64 Tahun' => '60-64',
                '65-69 Tahun' => '65-69',
                '70-74 Tahun' => '70-74',
                '75+ Tahun'   => '75+',
            ],
        ],

        // Sub-kolom: L | P | Total. Ejaan di berkas dipertahankan apa adanya di
        // teks_header ("SLTP"), sedangkan label memakai ejaan yang dipakai
        // halaman Sosial ("SMP/Sederajat").
        'Pendidikan' => [
            'jenis'  => 'pendidikan',
            'offset' => 2,
            'kolom'  => [
                'Tidak/Belum Sekolah' => 'Tidak/Belum Sekolah',
                'Belum Tamat SD'      => 'Belum Tamat SD/Sederajat',
                'Tamat SD/Sederajat'  => 'Tamat SD/Sederajat',
                'SMP/Sederajat'       => 'SLTP/Sederajat',
                'SMA/Sederajat'       => 'SLTA/Sederajat',
                'Diploma I/II'        => 'DIPLOMA I/II',
                'Diploma III'         => 'AKADEMI/DIPLOMA III/S.MUDA',
                'Diploma IV/S1'       => 'DIPLOMA IV/STRATA I',
                'S2'                  => 'STRATA II',
                'S3'                  => 'STRATA III',
            ],
        ],

        /*
         * Dipakai sheet KelompokPekerjaan (11 kelompok), BUKAN sheet Pekerjaan.
         *
         * Sheet 'Pekerjaan' hanya memuat rekap se-kota tanpa rincian kelurahan,
         * jadi tidak bisa masuk star schema yang selalu butuh wilayah_id.
         * 'Pekerjaan_v2' sebenarnya juga per kelurahan (17 jenis, offset 0) dan
         * bisa dipakai bila suatu saat butuh rincian yang lebih halus.
         *
         * Perhatikan spasi ganjil di berkas ("Aparatur/ Pejabat Negara") —
         * teks_header wajib menirunya persis, label-nya yang dirapikan.
         */
        'KelompokPekerjaan' => [
            'jenis'  => 'pekerjaan',
            'offset' => 2,
            'kolom'  => [
                'Aparatur/Pejabat Negara' => 'Aparatur/ Pejabat Negara',
                'Tenaga Pengajar'         => 'Tenaga Pengajar',
                'Wiraswasta'              => 'Wiraswasta',
                'Pertanian/Peternakan'    => 'Pertanian/ Peternakan',
                'Nelayan'                 => 'Nelayan',
                'Agama dan Kepercayaan'   => 'Agama dan Kepercayaan',
                'Pelajar/Mahasiswa'       => 'Pelajar/Mahasiswa',
                'Tenaga Kesehatan'        => 'Tenaga Kesehatan',
                'Belum/Tidak Bekerja'     => 'Belum/Tidak Bekerja',
                'Pensiunan'               => 'Pensiunan',
                'Lainnya'                 => 'Lainnya',
            ],
        ],

        /*
         * SENGAJA memakai sheet Agama_JK, BUKAN sheet 'Agama'. Sheet 'Agama'
         * seluruh isinya #REF! (rumus lintas-berkas yang putus di file DKB
         * aslinya) — bukan bug importer, tapi sheet itu sendiri kosong.
         * Agama_JK berisi angka yang sama persis, hanya dengan header
         * bertingkat (per jenis kelamin) sehingga offset-nya 2 (mendarat di
         * sub-kolom L+P), sama seperti StatKawin_JK/Disabilitas/GolDar_JK.
         */
        'Agama_JK' => [
            'jenis'  => 'agama',
            'offset' => 2,
            'kolom'  => [
                'Islam'       => 'Islam',
                'Kristen'     => 'Kristen',
                'Katolik'     => 'Katholik',
                'Hindu'       => 'Hindu',
                'Buddha'      => 'Budha',
                'Konghucu'    => 'Khong Hu Chu',
                'Kepercayaan' => 'Kepercayaan',
            ],
        ],

        // Header langsung ("Rasio Jenis Kelamin"), tanpa grup — offset 0.
        // Laki-laki/Perempuan/Jumlah sengaja tidak diulang di sini karena
        // sudah ada di sheet PendudukJK.
        'RasioJenisKelamin' => [
            'jenis'  => 'rasio_jenis_kelamin',
            'offset' => 0,
            'kolom'  => [
                'Rasio Jenis Kelamin' => 'Rasio Jenis Kelamin',
            ],
        ],

        // Header ada di baris 8 (bukan baris 5 seperti sheet lain) karena ada
        // paragraf definisi di atasnya — tetap ketemu karena pencarian header
        // menyusuri 12 baris pertama, bukan baris tetap.
        'KepadatanPenduduk' => [
            'jenis'  => 'kepadatan_penduduk',
            'offset' => 0,
            'kolom'  => [
                'Luas Wilayah (km2)'    => 'Luas Wilayah (km2)',
                'Kepadatan (Jiwa/km2)'  => 'Kepadatan (Jiwa/km2)',
            ],
        ],

        // Menutup dua permintaan sekaligus: usia muda/produktif/tua DAN rasio
        // ketergantungan. Header ber-wrap ("Usia Muda\n(0-14 Tahun)") — aman
        // karena normalkan() mengubah newline jadi spasi tunggal.
        'RasioKetergantungan' => [
            'jenis'  => 'rasio_ketergantungan',
            'offset' => 0,
            'kolom'  => [
                'Usia Muda (0-14 Tahun)'      => 'Usia Muda (0-14 Tahun)',
                'Usia Produktif (15-64 Tahun)' => 'Usia Produktif (15-64 Tahun)',
                'Usia Tua (65+ Tahun)'         => 'Usia Tua (65+)',
                'Rasio Ketergantungan (%)'     => 'Rasio Ketergantungan (%)',
            ],
        ],

        /*
         * Sheet ini punya DUA blok tabel identik berdampingan (kolom B dan
         * kolom J sama-sama "Wilayah", isinya kembar). Aman: PemetaKolom
         * mencari kecocokan PERSIS pertama menyusuri baris lalu kolom, jadi
         * otomatis mendarat di blok pertama (kiri) tanpa konfigurasi khusus.
         * Jumlah total kepala keluarga sudah ada di sheet KK — di sini
         * hanya rincian jenis kelamin kepala keluarga yang ditambahkan.
         */
        'Jumlah_KK' => [
            'jenis'  => 'kepala_keluarga_jk',
            'offset' => 0,
            'kolom'  => [
                'Kepala Keluarga Laki-laki' => 'Laki-laki',
                'Kepala Keluarga Perempuan' => 'Perempuan',
            ],
        ],

        // "Rasio Pindah Datang" = rasio arus (pindah+datang)/penduduk, BUKAN
        // migrasi neto. Migrasi neto (datang-pindah) dihitung sebagai
        // turunan dari sheet Pindah_&_Datang yang sudah ada, bukan di sini.
        'RasioPindahDatang' => [
            'jenis'  => 'rasio_pindah_datang',
            'offset' => 0,
            'kolom'  => [
                'Rasio Pindah-Datang' => 'Rasio Pindah Datang',
            ],
        ],

        // Rincian usia 0-5 & 0-17 tahun, melengkapi AktaLahirAll (semua usia)
        // yang sudah ada. Pola identik AktaLahirAll, header leaf langsung
        // (tanpa grup+offset) sehingga blok kedua di sebelah kanan otomatis
        // terlewati oleh aturan "kecocokan persis pertama".
        'AktaLahir0-5' => [
            'jenis'  => 'akta_lahir_0_5',
            'offset' => 0,
            'kolom'  => [
                'Memiliki Akta Lahir 0-5 Tahun'       => 'Memiliki Akta Lahir',
                'Belum Memiliki Akta Lahir 0-5 Tahun'  => 'Belum Memiliki Akta Lahir',
                'Jumlah Anak Usia 0-5 Tahun'           => 'Jumlah Anak Usia 0-5 Tahun',
            ],
        ],

        'AktaLahir0-17' => [
            'jenis'  => 'akta_lahir_0_17',
            'offset' => 0,
            'kolom'  => [
                'Memiliki Akta Lahir 0-17 Tahun'      => 'Memiliki Akta Lahir',
                'Belum Memiliki Akta Lahir 0-17 Tahun' => 'Belum Memiliki Akta Lahir',
                'Jumlah Penduduk Usia 0-17 Tahun'      => 'Jumlah Penduduk Usia 0-17 Tahun',
            ],
        ],

        /*
         * Status hubungan dalam keluarga. Header grup di baris judul (mis.
         * "KEPALA KELUARGA"), sub-kolom L | P | L+P — offset 2 mendarat di
         * L+P, pola sama dengan StatKawin_JK/Disabilitas/GolDar_JK.
         *
         * PENTING — 'mulai' => 4 WAJIB ADA. Sheet ini punya sisa tabel
         * rekap/pivot di baris 3-4 (KOTA CIMAHI saja, bukan per kelurahan)
         * yang kebetulan memuat teks PERSIS SAMA dengan header tabel utama
         * ("WILAYAH", "KEPALA KELUARGA", dst). Tanpa 'mulai', pencarian
         * header akan mendarat di sisa rekap itu (baris 3) dan mengambil
         * data dari kolom yang salah sama sekali. Baris 0-3 dilompati
         * supaya pencarian langsung mulai dari baris 5 (header asli).
         * Dibuktikan lewat Uji Coba Pemetaan sebelum dipakai produksi.
         */
        'SHBKEL' => [
            'jenis'  => 'status_hubungan_keluarga',
            'offset' => 2,
            'mulai'  => 4,
            // Berkas Semester I 2025 menamai sheet ini 'SHBKEL(rev)', bukan
            // 'SHBKEL' — dicoba sebagai alias bila nama utama tidak ada.
            'alias'  => 'SHBKEL(rev)',
            'kolom'  => [
                'Kepala Keluarga' => 'KEPALA KELUARGA',
                'Suami'           => 'SUAMI',
                'Isteri'          => 'ISTERI',
                'Anak'            => 'ANAK',
                'Menantu'         => 'MENANTU',
                'Cucu'            => 'CUCU',
                'Orang Tua'       => 'ORANG TUA',
                'Mertua'          => 'MERTUA',
                'Famili Lain'     => 'FAMILI LAIN',
                'Pembantu'        => 'PEMBANTU',
                'Lainnya'         => 'LAINNYA',
            ],
        ],

        // Header grup status kawin, sub-kolom: Laki-laki | Perempuan | Total
        'StatKawin_JK' => [
            'jenis'  => 'status_kawin',
            'offset' => 2,
            'kolom'  => [
                'Belum Kawin' => 'Belum Kawin',
                'Kawin'       => 'Kawin',
                'Cerai Hidup' => 'Cerai Hidup',
                'Cerai Mati'  => 'Cerai Mati',
            ],
        ],

        // Sub-kolom: Laki-laki | Perempuan | Jumlah
        'KTP' => [
            'jenis'  => 'kepemilikan_ktp',
            'offset' => 2,
            'kolom'  => [
                'Wajib KTP'        => 'Wajib KTP',
                'Sudah Rekam KTP'  => 'Sudah Rekam KTP',
                'Belum Rekam KTP'  => 'Belum Rekam KTP',
                'Sudah Cetak KTP'  => 'Sudah Cetak KTP',
                'Belum Cetak KTP'  => 'Belum Cetak KTP',
            ],
        ],

        'KK' => [
            'jenis'  => 'kepemilikan_kk',
            'offset' => 0,
            'kolom'  => [
                'KK Sudah TTE'           => 'KK Sudah TTE',
                'KK Belum TTE'           => 'KK Belum TTE',
                'Jumlah Kepala Keluarga' => 'Jumlah Kepala Keluarga',
            ],
        ],

        'KIA' => [
            'jenis'  => 'kepemilikan_kia',
            'offset' => 0,
            'kolom'  => [
                'Memiliki KIA'                => 'Memiliki KIA',
                'Belum Memiliki KIA'          => 'Belum Memiliki KIA',
                'Jumlah Anak Usia 0-17 Tahun' => 'Jumlah Anak Usia 0-17 Tahun',
            ],
        ],

        'AktaLahirAll' => [
            'jenis'  => 'akta_lahir',
            'offset' => 0,
            'kolom'  => [
                'Memiliki Akta Lahir'       => 'Memiliki Akta Lahir',
                'Belum Memiliki Akta Lahir' => 'Belum Memiliki Akta Lahir',
                'Jumlah Penduduk'           => 'Jumlah Penduduk',
            ],
        ],

        'AkteKawin' => [
            'jenis'  => 'akta_kawin',
            'offset' => 0,
            'kolom'  => [
                'Memiliki Akta Kawin'       => 'Memiliki Akta Kawin',
                'Belum Memiliki Akta Kawin' => 'Belum Memiliki Akta Kawin',
                'Penduduk Status Kawin'     => 'Penduduk Status Kawin',
            ],
        ],

        'AkteCerai' => [
            'jenis'  => 'akta_cerai',
            'offset' => 0,
            'kolom'  => [
                'Memiliki Akta Cerai'       => 'Memiliki Akta Cerai',
                'Belum Memiliki Akta Cerai' => 'Belum Memiliki Akta Cerai',
                'Penduduk Status Cerai'     => 'Penduduk Status Cerai',
            ],
        ],

        // Sub-kolom: L | P | L+P
        'Disabilitas' => [
            'jenis'  => 'disabilitas',
            'offset' => 2,
            'kolom'  => [
                'Disabilitas Fisik'          => 'Disabilitas Fisik',
                'Disabilitas Netra/Buta'     => 'Netra/Buta',
                'Disabilitas Rungu/Wicara'   => 'Rungu/Wicara',
                'Disabilitas Mental/Jiwa'    => 'Mental/Jiwa',
                'Disabilitas Fisik & Mental' => 'Fisik & Mental',
                'Disabilitas Lainnya'        => 'Lainnya',
            ],
        ],

        // Sub-kolom: L | P | L+P
        'GolDar_JK' => [
            'jenis'  => 'golongan_darah',
            'offset' => 2,
            'kolom'  => [
                'A'          => 'A',
                'B'          => 'B',
                'AB'         => 'AB',
                'O'          => 'O',
                'Tidak Tahu' => 'Tidak Tahu',
            ],
        ],

        // Sex ratio penduduk WNA per kelurahan. Header langsung, offset 0.
        'WNA' => [
            'jenis'  => 'wna',
            'offset' => 0,
            'kolom'  => [
                'WNA Laki-laki' => 'Laki-laki',
                'WNA Perempuan' => 'Perempuan',
                'WNA Jumlah'    => 'Jumlah',
            ],
        ],

        // Umur median per kelurahan. Header langsung, offset 0.
        'UmurMedian' => [
            'jenis'  => 'umur_median',
            'offset' => 0,
            'kolom'  => [
                'Umur Median Laki-laki' => 'Median Laki-laki',
                'Umur Median Perempuan' => 'Median Perempuan',
                'Umur Median'           => 'Median Wilayah',
            ],
        ],

        // Laju Pertumbuhan Penduduk — sheet ini juga punya kolom penduduk
        // S2 2024 untuk pembanding, tapi TIDAK diimpor (kita tidak punya data
        // S2 2024 sendiri untuk disilangkan) — cukup ambil angka LPP-nya
        // yang sudah dihitung Disdukcapil sendiri.
        'LPP' => [
            'jenis'  => 'lpp',
            'offset' => 0,
            'kolom'  => [
                'Laju Pertumbuhan Penduduk (%)' => 'LPP (%)',
            ],
        ],

        // Penduduk usia sekolah per kelurahan. Header langsung, offset 0.
        'UsiaSekolah' => [
            'jenis'  => 'usia_sekolah',
            'offset' => 0,
            'kolom'  => [
                'Usia SD/Sederajat (6-12 Tahun)'          => 'USIA SD/SEDERAJAT',
                'Usia SLTP/Sederajat (13-15 Tahun)'       => 'USIA SLTP/SEDERAJAT',
                'Usia SLTA/Sederajat (16-18 Tahun)'       => 'USIA SLTA/SEDERAJAT',
                'Usia Perguruan Tinggi (19-24 Tahun)'     => 'USIA PERGURUAN TINGGI',
            ],
        ],

        /*
         * Rincian jenis pekerjaan per kelurahan (17 jenis) — melengkapi
         * KelompokPekerjaan yang sudah diimpor (11 KELOMPOK besar, bukan
         * jenis rinci). Sengaja jenis_indikator terpisah supaya tidak
         * tertukar dengan agregat kelompoknya.
         */
        'STATUS_PEKERJAAN v1' => [
            'jenis'  => 'jenis_pekerjaan',
            'offset' => 0,
            'kolom'  => [
                'Belum/Tidak Bekerja'    => 'Belum/Tidak Bekerja',
                'Mengurus Rumah Tangga'  => 'Mengurus Rumah Tangga',
                'Pelajar/Mahasiswa'      => 'Pelajar/ Mahasiswa',
                'Pensiunan'              => 'Pensiunan',
                'PNS'                    => 'PNS',
                'TNI'                    => 'TNI',
                'POLRI'                  => 'POLRI',
                'Karyawan Swasta'        => 'Karyawan Swasta',
                'Karyawan BUMN'          => 'Karyawan BUMN',
                'Karyawan BUMD'          => 'Karyawan BUMD',
                'Buruh Harian Lepas'     => 'Buruh Harian Lepas',
                'Petani/Pekebun'         => 'Petani/ Pekebun',
                'Wiraswasta'             => 'Wiraswasta',
                'Dosen'                  => 'Dosen',
                'Guru'                   => 'Guru',
                'Dokter'                 => 'Dokter',
                'Pekerjaan Lainnya'      => 'Pekerjaan Lainnya',
            ],
        ],

        /*
         * Proxy kelahiran untuk CBR/GFR — SATU baris saja diambil dari sheet
         * UmurTunggal (umur tunggal 0-99+ per kelurahan, bersusun terbalik
         * sama seperti KelompokUmur): baris "0" (usia 0 tahun) dipakai
         * sebagai proxy jumlah kelahiran, karena DKB tidak punya sheet
         * "jumlah kelahiran" tersendiri — ini metode yang sama dipakai buku
         * profil resmi (lihat catatan CBR/GFR di bagian bawah berkas ini).
         *
         * PENTING: sheet ini punya 4 tabel bersusun VERTIKAL (baris "UMUR"
         * muncul lagi di baris 136, 176, 216 — rollup kecamatan/kota).
         * cariBaris('0') otomatis berhenti di kecocokan PERTAMA (tabel
         * per-kelurahan paling atas), jadi aman tanpa konfigurasi tambahan.
         *
         * orientasi WAJIB 'kolom_urut' (bukan 'kolom' biasa) walau baris ini
         * baik-baik saja dicari lewat cariBaris('0') apa adanya — semua
         * baris konfigurasi untuk SATU sheet diproses sebagai satu grup, dan
         * hanya orientasi baris PERTAMA grup itu yang dipakai (lihat
         * PembacaSheetDkb::bacaSatuSheet()). Baris "Umur 1-99 Tahun" di
         * bawah WAJIB pakai 'kolom_urut' (lihat alasannya di sana), jadi
         * baris "Umur 0 Tahun" ini disamakan supaya satu sheet konsisten.
         */
        'UmurTunggal' => [
            'jenis'     => 'kelahiran_proxy',
            'offset'    => 2,
            'orientasi' => KonfigurasiImport::ORIENTASI_KOLOM_URUT,
            'kolom'     => [
                'Jumlah Penduduk Usia 0 Tahun' => '0',
            ],
        ],

        /*
         * Angkatan Kerja & TPAK — SATU-SATUNYA sheet berorientasi 'blok'.
         * Nama wilayah cuma ada di baris pertama tiap kelompok 10 baris
         * (satu per kelompok umur 15-19 s.d. 60-64); sisanya kosong. Nilai
         * label dijumlahkan (SUM) seluruh baris satu blok oleh
         * PembacaSheetDkb::bacaSheetBlok() — offset di sini SELALU offset
         * kolom Laki-laki; kolom Perempuan diambil otomatis di offset+1
         * lalu dijumlahkan. 'TPAK (%)' TIDAK didaftarkan di sini karena ia
         * dihitung (bukan dibaca): total Angkatan Kerja ÷ total Jumlah
         * Penduduk Usia Kerja, ditulis otomatis oleh bacaSheetBlok().
         *
         * mulai=0 aman di sini (beda dari SHBKEL) — baris 1-4 blok
         * per-kelurahan sheet ini kosong total, tidak ada sisa rekap yang
         * bentrok dengan header aslinya.
         */
        'AngkatanKerja' => [
            'jenis'     => 'angkatan_kerja',
            'offset'    => 0,
            'orientasi' => KonfigurasiImport::ORIENTASI_BLOK,
            'kolom'     => [
                'Jumlah Penduduk Usia Kerja' => 'Jumlah Penduduk',
                'Angkatan Kerja'             => 'Angkatan Kerja',
            ],
        ],

        /*
         * Kepala Keluarga (KK) menurut status perkawinan & jenis kelamin
         * (Tabel 26) — sub-kolom Total saja, sama seperti StatKawin_JK di
         * atas (versi penduduk, bukan KK).
         *
         * Berkas Semester I 2025 menamai sheet ini 'KK_StatKawin_JK(rev)',
         * bukan 'KK_StatKawin_JK' — sama seperti pola SHBKEL(rev).
         */
        'KK_StatKawin_JK' => [
            'jenis'  => 'kk_status_kawin',
            'offset' => 2,
            'alias'  => 'KK_StatKawin_JK(rev)',
            'kolom'  => [
                'Belum Kawin' => 'Belum Kawin',
                'Kawin'       => 'Kawin',
                'Cerai Hidup' => 'Cerai Hidup',
                'Cerai Mati'  => 'Cerai Mati',
            ],
        ],

        /*
         * Kepala Keluarga (KK) menurut kelompok pekerjaan (Tabel 27, versi
         * kelompok besar — 11 kelompok) — sub-kolom L+P saja.
         */
        'KK_KelPekerjaan' => [
            'jenis'  => 'kk_kelompok_pekerjaan',
            'offset' => 2,
            'alias'  => 'KK_KelPekerjaan(rev)',
            'kolom'  => [
                'Aparatur/Pejabat Negara' => 'Aparatur/ Pejabat Negara',
                'Tenaga Pengajar'         => 'Tenaga Pengajar',
                'Wiraswasta'              => 'Wiraswasta',
                'Pertanian/Peternakan'    => 'Pertanian/ Peternakan',
                'Nelayan'                 => 'Nelayan',
                'Agama dan Kepercayaan'   => 'Agama dan Kepercayaan',
                'Pelajar/Mahasiswa'       => 'Pelajar/Mahasiswa',
                'Tenaga Kesehatan'        => 'Tenaga Kesehatan',
                'Belum/Tidak Bekerja'     => 'Belum/Tidak Bekerja',
                'Pensiunan'               => 'Pensiunan',
                'Lainnya'                 => 'Lainnya',
            ],
        ],

        // Kepala Keluarga (KK) menurut agama — sub-kolom L+P saja.
        'KK_Agama' => [
            'jenis'  => 'kk_agama',
            'offset' => 2,
            'alias'  => 'KK_Agama(rev)',
            'kolom'  => [
                'Islam'       => 'Islam',
                'Kristen'     => 'Kristen',
                'Katolik'     => 'Katholik',
                'Hindu'       => 'Hindu',
                'Buddha'      => 'Budha',
                'Konghucu'    => 'Khong Hu Chu',
                'Kepercayaan' => 'Kepercayaan',
            ],
        ],
    ];

    /**
     * Sheet Pindah_&_Datang ditangani terpisah karena satu sheet memasok DUA
     * jenis_indikator sekaligus, sedangkan struktur $profil di atas
     * mengasumsikan satu sheet = satu jenis_indikator.
     */
    private array $mobilitas = [
        ['jenis' => 'mobilitas_pindah', 'label' => 'Pindah', 'header' => 'Pindah'],
        ['jenis' => 'mobilitas_datang', 'label' => 'Datang', 'header' => 'Datang'],
    ];

    /**
     * Penyebut GFR (perempuan usia subur 15-49 tahun) — ditangani terpisah
     * karena sheet 'KelompokUmur' sudah dipakai $profil di atas dengan
     * offset 2 (Total); di sini yang dibutuhkan offset 1 (Perempuan) untuk
     * TUJUH kelompok umur yang sama persis. Satu kunci array tidak bisa
     * menyimpan dua definisi offset berbeda untuk sheet yang sama, jadi
     * dipisah seperti $mobilitas di atas.
     */
    private array $perempuanUsiaSubur = [
        '15-19 Tahun' => '15-19',
        '20-24 Tahun' => '20-24',
        '25-29 Tahun' => '25-29',
        '30-34 Tahun' => '30-34',
        '35-39 Tahun' => '35-39',
        '40-44 Tahun' => '40-44',
        '45-49 Tahun' => '45-49',
    ];

    /**
     * 4 sheet "penerbitan dokumen" — rekap SE-KOTA per bulan (ORIENTASI_KOTA),
     * BUKAN per kelurahan. Baris dicari lewat NAMA BULAN (unik per sheet,
     * aman dari duplikasi), nilainya diambil dari kolom D (offset 3) untuk
     * "dilaporkan" dan kolom E (offset 4) untuk "diterbitkan/TTE" — indeks
     * kolom LANGSUNG, bukan hasil pencarian teks header, karena header
     * kolom D/E di berkas sumbernya sendiri salah tempel (bunyinya sama
     * persis "...Akta Perkawinan..." di 3 dari 4 sheet walau sheetnya soal
     * Pengakuan/Pengesahan Anak/Perceraian) — label yang disimpan di sini
     * sudah dibetulkan sesuai sheetnya masing-masing.
     */
    private array $terbitDokumen = [
        ['sheet' => 'TerbitPengakuanAnak',  'jenis' => 'terbit_pengakuan_anak',  'label_lapor' => 'Pelaporan Akta Pengakuan Anak',  'label_tte' => 'Akta Pengakuan Anak Diterbitkan (TTE)'],
        ['sheet' => 'TerbitPengesahanAnak', 'jenis' => 'terbit_pengesahan_anak', 'label_lapor' => 'Pelaporan Akta Pengesahan Anak', 'label_tte' => 'Akta Pengesahan Anak Diterbitkan (TTE)'],
        ['sheet' => 'TerbitAktaKawin',      'jenis' => 'terbit_akta_kawin',      'label_lapor' => 'Pelaporan Akta Perkawinan',      'label_tte' => 'Akta Perkawinan Diterbitkan (TTE)'],
        ['sheet' => 'TerbitAktaCerai',      'jenis' => 'terbit_akta_cerai',      'label_lapor' => 'Pelaporan Akta Perceraian',      'label_tte' => 'Akta Perceraian Diterbitkan (TTE)'],
    ];

    private array $bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * ASFR (Age Specific Fertility Rate) — rekap SE-KOTA per kelompok umur
     * perempuan 15-49 tahun (ORIENTASI_KOTA, baris dicari lewat kelompok
     * umur, sama seperti bulan dipakai $terbitDokumen). TFR & "Jumlah
     * Penduduk" di sheet ini SENGAJA tidak diimpor: TFR cuma satu angka
     * yang muncul sekali (dapat dihitung ulang dari ASFR: TFR = 5 ×
     * ΣASFR ÷ 1000), dan kolom "Jumlah Penduduk" per kelompok umur di
     * sheet ini nilainya tidak masuk akal (mis. 240.330 untuk satu
     * kelompok umur, jauh lebih besar dari kelompok lain) — kemungkinan
     * rumus yang salah tautan di berkas sumbernya sendiri.
     */
    /*
     * Tingkat pendidikan yang jadi label baris pada sheet AngkatanKerjaPendidikan
     * (Tabel 38 buku profil) — teks harus PERSIS sama dengan isi sel di kolom
     * "Tingkat Pendidikan" pada sheet tersebut.
     */
    private array $tingkatPendidikanAk = [
        'TIDAK/BLM SEKOLAH', 'BELUM TAMAT SD/SEDERAJAT', 'TAMAT SD/SEDERAJAT',
        'SLTP/SEDERAJAT', 'SLTA/SEDERAJAT', 'DIPLOMA I/II',
        'AKADEMI/DIPLOMA III/S. MUDA', 'DIPLOMA IV/STRATA I', 'STRATA-II', 'STRATA-III',
    ];

    /*
     * Kelompok umur pada sheet Perkawinan_KU, urut PERSIS sesuai posisi
     * kolomnya di berkas (offset 0 = kolom pertama "00-04", dst) — dipakai
     * sebagai offset_kolom pada ORIENTASI_KECAMATAN, BUKAN teks pencarian.
     */
    private array $kelompokUmurPerkawinanKu = [
        '0-4 Tahun', '5-9 Tahun', '10-14 Tahun', '15-19 Tahun',
        '20-24 Tahun', '25-29 Tahun', '30-34 Tahun', '35-39 Tahun',
        '40-44 Tahun', '45-49 Tahun', '50-54 Tahun', '55-59 Tahun',
        '60-64 Tahun', '65-69 Tahun', '70-74 Tahun', '75+ Tahun',
    ];

    /*
     * Daftar lengkap 99 jenis pekerjaan KTP-EL pada sheet KK_Pekerjaan
     * (Tabel 27, kolom "JUMLAH") — dicari lewat ORIENTASI_KOTA per baris.
     * Urutannya BERBEDA antara berkas S1 dan S2 (sudah diverifikasi set-nya
     * SAMA persis, cuma urutan beda) — aman karena dicari lewat teks, bukan
     * posisi. Posisi kolom "JUMLAH" sendiri JUGA berbeda antar-semester
     * (S1 cuma 1 kolom nilai, S2 punya L/P tambahan sebelum JUMLAH) —
     * makanya dicari lewat teks_header_kolom, bukan offset tetap (lihat
     * catatan sama di loop AngkatanKerjaPendidikan).
     */
    private array $pekerjaanKtpEl = [
        'BELUM/TIDAK BEKERJA',
        'MENGURUS RUMAH TANGGA',
        'PELAJAR/MAHASISWA',
        'PENSIUNAN',
        'PEGAWAI NEGERI SIPIL (PNS)',
        'TENTARA NASIONAL INDONESIA (TNI)',
        'KEPOLISIAN RI (POLRI)',
        'PERDAGANGAN',
        'PETANI/PEKEBUN',
        'PETERNAK',
        'NELAYAN/PERIKANAN',
        'INDUSTRI',
        'KONSTRUKSI',
        'TRANSPORTASI',
        'KARYAWAN SWASTA',
        'KARYAWAN BUMN',
        'KARYAWAN BUMD',
        'KARYAWAN HONORER',
        'BURUH HARIAN LEPAS',
        'BURUH TANI/PERKEBUNAN',
        'BURUH NELAYAN/PERIKANAN',
        'BURUH PETERNAKAN',
        'PEMBANTU RUMAH TANGGA',
        'TUKANG CUKUR',
        'TUKANG LISTRIK',
        'TUKANG BATU',
        'TUKANG KAYU',
        'TUKANG SOL SEPATU',
        'TUKANG LAS/PANDAI BESI',
        'TUKANG JAHIT',
        'TUKANG GIGI',
        'PENATA RIAS',
        'PENATA BUSANA',
        'PENATA RAMBUT',
        'MEKANIK',
        'SENIMAN',
        'TABIB',
        'PARAJI',
        'PERANCANG BUSANA',
        'PENTERJEMAH',
        'IMAM MASJID',
        'PENDETA',
        'PASTOR',
        'WARTAWAN',
        'USTADZ/MUBALIGH',
        'JURU MASAK',
        'PROMOTOR ACARA',
        'ANGGOTA DPR RI',
        'ANGGOTA DPD RI',
        'ANGGOTA BPK',
        'PRESIDEN',
        'WAKIL PRESIDEN',
        'ANGGOTA MAHKAMAH KONSTITUSI',
        'ANGGOTA KABINET KEMENTRIAN',
        'DUTA BESAR',
        'GUBERNUR',
        'WAKIL GUBERNUR',
        'BUPATI',
        'WAKIL BUPATI',
        'WALIKOTA',
        'WAKIL WALIKOTA',
        'ANGGOTA DPRD PROP.',
        'ANGGOTA DPRD KAB./KOTA',
        'DOSEN',
        'GURU',
        'PILOT',
        'PENGACARA',
        'NOTARIS',
        'ARSITEK',
        'AKUNTAN',
        'KONSULTAN',
        'DOKTER',
        'BIDAN',
        'PERAWAT',
        'APOTEKER',
        'PSIKIATER/PSIKOLOG',
        'PENYIAR TELEVISI',
        'PENYIAR RADIO',
        'PELAUT',
        'PENELITI',
        'SOPIR',
        'PIALANG',
        'PARANORMAL',
        'PEDAGANG',
        'PERANGKAT DESA',
        'KEPALA DESA',
        'BIARAWAN/BIARAWATI',
        'WIRASWASTA',
        'ANGGOTA LEMBAGA TINGGI LAINNYA',
        'ARTIS',
        'ATLIT',
        'CHEFF',
        'MANAJER',
        'TENAGA TATA USAHA',
        'OPERATOR',
        'PEKERJA PENGOLAHAN KERAJINAN',
        'TEKNISI',
        'ASISTEN AHLI',
        'PEKERJAAN LAINNYA',
    ];

    /*
     * Kepala Keluarga (KK) menurut tingkat pendidikan (Tabel 28) — label
     * kanonik => teks_header PERSIS di sheet KK_Pendidikan.
     *
     * SENGAJA impor L dan P terpisah (offset 0/1), BUKAN kolom "Total"
     * (offset 2) seperti sheet KK_* lain — sudah diverifikasi kolom Total
     * untuk 4 kelurahan kecamatan Cimahi Utara (Pasirkaliki/Cibabat/
     * Citeureup/Cipageran) berisi ANGKA STATIS YANG SALAH di berkas sumber
     * (bukan rumus =L+P seperti baris-baris lain di sheet yang sama —
     * kesalahan entri manual di file aslinya, dicek langsung ke rumus
     * mentah tiap sel). L+P sendiri tetap benar di seluruh baris, jadi
     * totalnya dihitung ulang saat ditampilkan, bukan diimpor apa adanya.
     */
    private array $kkPendidikan = [
        'Tidak/Belum Sekolah'      => 'Tidak/Belum Sekolah',
        'Belum Tamat SD/Sederajat' => 'Belum Tamat SD/Sederajat',
        'Tamat SD/Sederajat'       => 'Tamat SD/Sederajat',
        'SLTP/Sederajat'           => 'SLTP/Sederajat',
        'SLTA/Sederajat'           => 'SLTA/Sederajat',
        'Diploma I/II'             => 'DIPLOMA I/II',
        'Akademi/Diploma III'      => 'AKADEMI/DIPLOMA III/S.MUDA',
        'Diploma IV/Strata I'      => 'DIPLOMA IV/STRATA I',
        'Strata II'                => 'STRATA II',
        'Strata III'               => 'STRATA III',
    ];

    /** Judul blok PERSIS di sheet Perkawinan_KU => jenis_indikator. */
    private array $blokPerkawinanKu = [
        'BELUM KAWIN MENURUT KELOMPOK UMUR' => 'perkawinan_ku_belum_kawin',
        'KAWIN MENURUT KELOMPOK UMUR'       => 'perkawinan_ku_kawin',
        'CERAI HIDUP MENURUT KELOMPOK UMUR' => 'perkawinan_ku_cerai_hidup',
        'CERAI MATI MENURUT KELOMPOK UMUR'  => 'perkawinan_ku_cerai_mati',
    ];

    /** Judul blok PERSIS di sheet KK_Kawin_KelUmur => jenis_indikator. */
    private array $blokKkKawinKelUmur = [
        'BELUM KAWIN MENURUT KELOMPOK UMUR' => 'kk_kawin_ku_belum_kawin',
        'KAWIN MENURUT KELOMPOK UMUR'       => 'kk_kawin_ku_kawin',
        'CERAI HIDUP MENURUT KELOMPOK UMUR' => 'kk_kawin_ku_cerai_hidup',
        'CERAI MATI MENURUT KELOMPOK UMUR'  => 'kk_kawin_ku_cerai_mati',
    ];

    /** Judul blok PERSIS di sheet Agama_KelUmur_Kec => jenis_indikator. */
    private array $blokAgamaKelUmurKec = [
        'AGAMA ISLAM BERDASARKAN KELOMPOK UMUR'     => 'agama_ku_islam',
        'AGAMA KRISTEN BERDASARKAN KELOMPOK UMUR'   => 'agama_ku_kristen',
        'AGAMA KATHOLIK BERDASARKAN KELOMPOK UMUR'  => 'agama_ku_katholik',
        'AGAMA HINDU BERDASARKAN KELOMPOK UMUR'     => 'agama_ku_hindu',
        'AGAMA BUDHA BERDASARKAN KELOMPOK UMUR'     => 'agama_ku_budha',
        'AGAMA KHONGHUCU BERDASARKAN KELOMPOK UMUR' => 'agama_ku_khonghucu',
        'AGAMA KEPERCAYAAN MENURUT KELOMPOK UMUR'   => 'agama_ku_kepercayaan',
    ];

    /** Judul blok PERSIS di sheet GolDar_KelUmur_Kec => jenis_indikator. */
    private array $blokGolDarKelUmurKec = [
        'GOLONGAN DARAH A'   => 'goldar_ku_a',
        'GOLONGAN DARAH B'   => 'goldar_ku_b',
        'GOLONGAN DARAH AB'  => 'goldar_ku_ab',
        'GOLONGAN DARAH O'   => 'goldar_ku_o',
        'GOLONGAN DARAH A+'  => 'goldar_ku_a_plus',
        'GOLONGAN DARAH A-'  => 'goldar_ku_a_minus',
        'GOLONGAN DARAH B+'  => 'goldar_ku_b_plus',
        'GOLONGAN DARAH B-'  => 'goldar_ku_b_minus',
        'GOLONGAN DARAH O+'  => 'goldar_ku_o_plus',
        'GOLONGAN DARAH O-'  => 'goldar_ku_o_minus',
        'GOLONGAN DARAH AB+' => 'goldar_ku_ab_plus',
        'GOLONGAN DARAH AB-' => 'goldar_ku_ab_minus',
    ];

    /*
     * Kelompok umur SEKOLAH pada sheet Disabilitas_USklh — beda dari
     * kelompok umur 5-tahunan biasa ("00-04" dst) DAN punya satu baris
     * header ekstra (L|P|L+P) di bawah baris kelompok umurnya (sheet
     * kecamatan lain tidak punya ini). Makanya angkur kolomnya
     * (teks_header_kolom, dipakai lewat parameter simpan() di bawah)
     * disetel ke "L" (baris L|P|L+P itu sendiri), BUKAN "4-6" (satu baris
     * di atasnya) — supaya 3 baris kecamatan yang dibaca (angkur+1/+2/+3)
     * jatuh tepat di baris data, bukan di baris header. Tiap kelompok umur
     * punya 3 sub-kolom (L|P|L+P) — Total-nya di offset (indeks*3 + 2).
     */
    private array $kelompokUmurSekolahDisabilitas = ['4-6', '7-12', '13-15', '16-18'];

    /*
     * Jenis pekerjaan pada sheet Disabilitas_Pekerjaan (Tabel 22) — se-Kota,
     * hanya jenis yang benar-benar ADA penyandang disabilitasnya (25 dari
     * 99 kode KTP-EL). Kolom L/P dicari lewat header grup "Jenis Kelamin"
     * (offset 0=L, 2=P) — posisi kolomnya BERGESER 1 kolom antara S1 & S2
     * (S1 tanpa kolom "NO" di depan), makanya teks_header_kolom dipakai
     * alih-alih offset tetap.
     */
    private array $pekerjaanDisabilitas = [
        'BELUM/TIDAK BEKERJA', 'MENGURUS RUMAH TANGGA', 'PELAJAR/MAHASISWA', 'PENSIUNAN',
        'PEGAWAI NEGERI SIPIL (PNS)', 'TENTARA NASIONAL INDONESIA (TNI)', 'PERDAGANGAN',
        'PETANI/PEKEBUN', 'KARYAWAN SWASTA', 'KARYAWAN BUMN', 'KARYAWAN HONORER',
        'BURUH HARIAN LEPAS', 'BURUH TANI/PERKEBUNAN', 'PEMBANTU RUMAH TANGGA',
        'TUKANG CUKUR', 'TUKANG BATU', 'TUKANG JAHIT', 'SENIMAN', 'TABIB', 'GURU',
        'SOPIR', 'PEDAGANG', 'WIRASWASTA', 'ANGGOTA LEMBAGA TINGGI LAINNYA', 'ATLIT',
    ];

    /** Judul blok PERSIS di sheet Disabilitas_KU => jenis_indikator. */
    private array $blokDisabilitasKu = [
        'PENYANDANG DISABILITAS FISIK'         => 'disabilitas_ku_fisik',
        'PENYANDANG DISABILITAS NETRA/BUTA'    => 'disabilitas_ku_netra',
        'PENYANDANG DISABILITAS RUNGU/WICARA'  => 'disabilitas_ku_rungu_wicara',
        'PENYANDANG DISABILITAS MENTAL/JIWA'   => 'disabilitas_ku_mental',
        'PENYANDANG DISABILITAS FISIK DAN MENTAL' => 'disabilitas_ku_fisik_mental',
        'PENYANDANG DISABILITAS LAINNYA'       => 'disabilitas_ku_lainnya',
    ];

    private array $asfrKelompokUmur = [
        '15-19 Tahun' => '15-19',
        '20-24 Tahun' => '20-24',
        '25-29 Tahun' => '25-29',
        '30-34 Tahun' => '30-34',
        '35-39 Tahun' => '35-39',
        '40-44 Tahun' => '40-44',
        '45-49 Tahun' => '45-49',
    ];

    public function run(): void
    {
        foreach ($this->profil as $sheet => $def) {
            foreach ($def['kolom'] as $label => $teksHeader) {
                $this->simpan(
                    $sheet,
                    $def['jenis'],
                    (string) $label,
                    $teksHeader,
                    $def['offset'],
                    $def['orientasi'] ?? KonfigurasiImport::ORIENTASI_BARIS,
                    $def['mulai'] ?? 0,
                    $def['alias'] ?? null,
                );
            }
        }

        // Sub-kolom Pindah/Datang: Laki-laki | Perempuan | Jumlah
        foreach ($this->mobilitas as $m) {
            $this->simpan('Pindah_&_Datang', $m['jenis'], $m['label'], $m['header'], 2);
        }

        // Penyebut GFR: perempuan usia subur, offset 1 dari sheet KelompokUmur yang sama.
        foreach ($this->perempuanUsiaSubur as $label => $teksHeader) {
            $this->simpan('KelompokUmur', 'perempuan_usia_subur', $label, $teksHeader, 1, KonfigurasiImport::ORIENTASI_KOLOM);
        }

        /*
         * Piramida penduduk — kolom Laki-laki (offset 0) & Perempuan (offset 1)
         * dari sheet KelompokUmur yang sama, untuk SELURUH 16 kelompok umur
         * (bukan cuma 7 kelompok usia subur di atas). Sheet ini sudah dipakai
         * di $profil untuk kolom Total (offset 2) — ini murni TAMBAHAN dua
         * jenis_indikator baru, tidak mengubah satu pun mapping yang sudah ada.
         */
        foreach ($this->profil['KelompokUmur']['kolom'] as $label => $teksHeader) {
            $this->simpan('KelompokUmur', 'kelompok_umur_l', $label, $teksHeader, 0, KonfigurasiImport::ORIENTASI_KOLOM);
            $this->simpan('KelompokUmur', 'kelompok_umur_p', $label, $teksHeader, 1, KonfigurasiImport::ORIENTASI_KOLOM);
        }

        // Umur tunggal PENUH (1-99 tahun) per kelurahan — usia 0 sudah
        // ditangani terpisah di atas sebagai 'kelahiran_proxy' (makna beda:
        // proksi kelahiran, bukan sekadar cacah umur biasa).
        for ($umur = 1; $umur <= 99; $umur++) {
            $this->simpan('UmurTunggal', 'umur_tunggal', "Umur {$umur} Tahun", (string) $umur, 2, KonfigurasiImport::ORIENTASI_KOLOM_URUT);
        }

        // 4 sheet penerbitan dokumen — rekap kota per bulan.
        foreach ($this->terbitDokumen as $d) {
            foreach ($this->bulan as $bulan) {
                $this->simpan($d['sheet'], $d['jenis'], "{$bulan} - {$d['label_lapor']}", $bulan, 3, KonfigurasiImport::ORIENTASI_KOTA);
                $this->simpan($d['sheet'], $d['jenis'], "{$bulan} - {$d['label_tte']}", $bulan, 4, KonfigurasiImport::ORIENTASI_KOTA);
            }
        }

        // ASFR — rekap kota per kelompok umur perempuan. Hanya dua komponen
        // MENTAH (bilangan bulat) yang diimpor, bukan rasio ASFR itu sendiri
        // — ASFR bernilai desimal kecil (mis. 0,595 untuk 45-49 tahun) yang
        // akan hilang presisinya kalau disimpan sebagai bilangan bulat
        // (data_agregat.jumlah). Rasionya dihitung ulang saat ditampilkan
        // (kelahiran ÷ perempuan × 1000), sama seperti pola TPAK/CBR/GFR.
        foreach ($this->asfrKelompokUmur as $label => $teksHeader) {
            $this->simpan('ASFR', 'asfr_jumlah_perempuan', "Jumlah Perempuan {$label}", $teksHeader, 2, KonfigurasiImport::ORIENTASI_KOTA);
            $this->simpan('ASFR', 'asfr_kelahiran_hidup', "Kelahiran Hidup {$label}", $teksHeader, 3, KonfigurasiImport::ORIENTASI_KOTA);
        }

        /*
         * Angkatan kerja per tingkat pendidikan (Tabel 38) — se-Kota.
         *
         * Posisi kolom "Jumlah Penduduk"/"Bukan Angkatan Kerja"/"Angkatan
         * Kerja"/"Tidak Bekerja"/"Bekerja" BERBEDA antara berkas S1 dan S2:
         * S1 menyisipkan kolom rincian laki-laki/perempuan (BAK_L, BAK_P,
         * AK_L, dst) sebelum kolom totalnya, S2 tidak. Karena itu kolomnya
         * dicari lewat teks header (teks_header_kolom), BUKAN offset tetap —
         * teks headernya sendiri identik persis di kedua berkas. Sudah
         * diverifikasi: Jumlah Penduduk = Bukan AK + AK, dan Angkatan Kerja
         * = Tidak Bekerja + Bekerja, konsisten persis di kedua berkas
         * memakai pemetaan ini.
         */
        foreach ($this->tingkatPendidikanAk as $label) {
            $this->simpan('AngkatanKerjaPendidikan', 'ak_pendidikan_jumlah_penduduk', "Jumlah Penduduk ({$label})", $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'Jumlah Penduduk');
            $this->simpan('AngkatanKerjaPendidikan', 'ak_pendidikan_bukan_ak', "Bukan Angkatan Kerja ({$label})", $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'Bukan Angkatan Kerja');
            $this->simpan('AngkatanKerjaPendidikan', 'ak_pendidikan_angkatan_kerja', "Angkatan Kerja ({$label})", $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'Angkatan Kerja');
            $this->simpan('AngkatanKerjaPendidikan', 'ak_pendidikan_tidak_bekerja', "Tidak Bekerja ({$label})", $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'Tidak Bekerja');
            $this->simpan('AngkatanKerjaPendidikan', 'ak_pendidikan_bekerja', "Bekerja ({$label})", $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'Bekerja');
        }

        /*
         * Status perkawinan menurut kelompok umur, PER KECAMATAN (Tabel 17) —
         * sheet Perkawinan_KU. Wilayahnya cuma 3 kecamatan (bukan 15
         * kelurahan), jadi pakai ORIENTASI_KECAMATAN + pseudo-wilayah
         * Cimahi Selatan/Tengah/Utara (lihat migrasi is_kecamatan).
         *
         * teks_header = judul blok status perkawinan (dipakai cari BARIS
         * judulnya). offset_kolom = posisi kelompok umur (dipakai cari KOLOM
         * lewat teks "00-04" pada baris sub-header, bukan offset tetap) —
         * lihat PembacaSheetDkb::bacaSheetKecamatan().
         */
        foreach ($this->blokPerkawinanKu as $judulBlok => $jenis) {
            foreach ($this->kelompokUmurPerkawinanKu as $offset => $label) {
                $this->simpan('Perkawinan_KU', $jenis, $label, $judulBlok, $offset, KonfigurasiImport::ORIENTASI_KECAMATAN);
            }
        }

        // Sama seperti Perkawinan_KU, tapi untuk KEPALA KELUARGA (Tabel 29).
        foreach ($this->blokKkKawinKelUmur as $judulBlok => $jenis) {
            foreach ($this->kelompokUmurPerkawinanKu as $offset => $label) {
                $this->simpan('KK_Kawin_KelUmur', $jenis, $label, $judulBlok, $offset, KonfigurasiImport::ORIENTASI_KECAMATAN);
            }
        }

        // Agama menurut kelompok umur PER KECAMATAN (Tabel 33).
        foreach ($this->blokAgamaKelUmurKec as $judulBlok => $jenis) {
            foreach ($this->kelompokUmurPerkawinanKu as $offset => $label) {
                $this->simpan('Agama_KelUmur_Kec', $jenis, $label, $judulBlok, $offset, KonfigurasiImport::ORIENTASI_KECAMATAN);
            }
        }

        // Golongan darah menurut kelompok umur PER KECAMATAN (Tabel 40).
        foreach ($this->blokGolDarKelUmurKec as $judulBlok => $jenis) {
            foreach ($this->kelompokUmurPerkawinanKu as $offset => $label) {
                $this->simpan('GolDar_KelUmur_Kec', $jenis, $label, $judulBlok, $offset, KonfigurasiImport::ORIENTASI_KECAMATAN);
            }
        }

        // Penyandang disabilitas menurut kelompok umur PER KECAMATAN (Tabel 36).
        foreach ($this->blokDisabilitasKu as $judulBlok => $jenis) {
            foreach ($this->kelompokUmurPerkawinanKu as $offset => $label) {
                $this->simpan('Disabilitas_KU', $jenis, $label, $judulBlok, $offset, KonfigurasiImport::ORIENTASI_KECAMATAN);
            }
        }

        // Penyandang disabilitas menurut kelompok umur SEKOLAH PER KECAMATAN (Tabel 15).
        foreach ($this->blokDisabilitasKu as $judulBlok => $jenisKu) {
            $jenis = str_replace('disabilitas_ku_', 'disabilitas_usklh_', $jenisKu);
            foreach ($this->kelompokUmurSekolahDisabilitas as $indeks => $label) {
                $this->simpan(
                    'Disabilitas_USklh',
                    $jenis,
                    "{$label} Tahun",
                    $judulBlok,
                    $indeks * 3 + 2, // Total (L+P), lihat catatan $kelompokUmurSekolahDisabilitas
                    KonfigurasiImport::ORIENTASI_KECAMATAN,
                    0,
                    null,
                    'L', // angkur kolom — baris L|P|L+P, bukan "00-04"/"4-6"
                );
            }
        }

        // Kepala Keluarga (KK) menurut tingkat pendidikan (Tabel 28) — L & P terpisah, lihat catatan di atas.
        foreach ($this->kkPendidikan as $label => $teksHeader) {
            $this->simpan('KK_Pendidikan', 'kk_pendidikan', "{$label} (L)", $teksHeader, 0, KonfigurasiImport::ORIENTASI_BARIS, 0, 'KK_Pendidikan(rev)');
            $this->simpan('KK_Pendidikan', 'kk_pendidikan', "{$label} (P)", $teksHeader, 1, KonfigurasiImport::ORIENTASI_BARIS, 0, 'KK_Pendidikan(rev)');
        }

        // Penyandang disabilitas menurut pekerjaan, se-Kota (Tabel 22).
        foreach ($this->pekerjaanDisabilitas as $label) {
            $labelRapi = ucwords(mb_strtolower($label), " /().");
            $this->simpan('Disabilitas_Pekerjaan', 'disabilitas_pekerjaan_l', "{$labelRapi} (L)", $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'JENIS KELAMIN');
            $this->simpan('Disabilitas_Pekerjaan', 'disabilitas_pekerjaan_p', "{$labelRapi} (P)", $label, 2, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'JENIS KELAMIN');
        }

        // Kepala Keluarga (KK) menurut jenis pekerjaan KTP-EL, penuh 99 jenis (Tabel 27).
        foreach ($this->pekerjaanKtpEl as $label) {
            $labelRapi = ucwords(mb_strtolower($label), " /().");
            $this->simpan('KK_Pekerjaan', 'kk_pekerjaan', $labelRapi, $label, 0, KonfigurasiImport::ORIENTASI_KOTA, 0, null, 'JUMLAH');
        }

        // Sheet 'Agama' (seluruhnya #REF! di berkas asli) diganti 'Agama_JK'
        // di atas. Baris konfigurasi lama untuk sheet 'Agama' dibuang supaya
        // tidak ikut dibaca lagi saat import (akan selalu gagal validasi
        // karena isinya galat rumus) dan tidak menumpuk sebagai sampah.
        KonfigurasiImport::query()
            ->where('nama_profil', KonfigurasiImport::PROFIL_BAWAAN)
            ->where('nama_sheet', 'Agama')
            ->delete();

        // Baris konfigurasi lama KK_Pendidikan (label tanpa akhiran L/P, pakai
        // kolom Total yang terbukti salah di 4 kelurahan) dibuang — diganti
        // pasangan label (L)/(P) di atas. Lihat catatan di $kkPendidikan.
        KonfigurasiImport::query()
            ->where('nama_profil', KonfigurasiImport::PROFIL_BAWAAN)
            ->where('nama_sheet', 'KK_Pendidikan')
            ->whereNotIn('label', collect($this->kkPendidikan)->keys()->flatMap(
                fn ($label) => ["{$label} (L)", "{$label} (P)"]
            ))
            ->delete();
    }

    private function simpan(
        string $sheet,
        string $jenis,
        string $label,
        string $teksHeader,
        int $offset,
        string $orientasi = KonfigurasiImport::ORIENTASI_BARIS,
        int $mulaiBaris = 0,
        ?string $aliasSheet = null,
        ?string $teksHeaderKolom = null,
    ): void {
        // updateOrCreate pada kunci unik: seeder boleh dijalankan ulang, dan
        // penyetelan offset yang sudah Admin lakukan lewat UI akan ditimpa
        // kembali ke nilai bawaan — itu memang perilaku yang diinginkan dari
        // sebuah "reset ke profil standar".
        KonfigurasiImport::updateOrCreate(
            [
                'nama_profil'     => KonfigurasiImport::PROFIL_BAWAAN,
                'nama_sheet'      => $sheet,
                'jenis_indikator' => $jenis,
                'label'           => $label,
            ],
            [
                'alias_sheet'                   => $aliasSheet,
                'teks_header'                   => $teksHeader,
                'offset_kolom'                  => $offset,
                'teks_header_kolom'             => $teksHeaderKolom,
                'orientasi'                     => $orientasi,
                'teks_header_wilayah'           => 'Wilayah',
                'baris_maks_pencarian_header'   => 12,
                'baris_mulai_pencarian_header'  => $mulaiBaris,
                'aktif'                         => true,
            ],
        );
    }
}
