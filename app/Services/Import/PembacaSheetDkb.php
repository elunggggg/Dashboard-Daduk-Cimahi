<?php

namespace App\Services\Import;

use App\Models\AliasWilayah;
use App\Models\DimWilayah;
use App\Models\KonfigurasiImport;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Pembaca berkas DKB mentah dari Disdukcapil (Mode A).
 *
 * Alur satu kali baca:
 *   1. ambil daftar sheet yang dibutuhkan profil (bukan semua 63 sheet)
 *   2. per sheet: cari baris header & kolom-kolomnya lewat PemetaKolom
 *   3. validasi struktur SEKALI (Prinsip 5), lalu baru menyusuri baris
 *   4. cocokkan tiap baris ke kelurahan lewat nama (Prinsip 2)
 *   5. berhenti setelah 15 kelurahan unik didapat (Prinsip 3)
 *
 * Tidak ada satu pun operasi tulis di kelas ini. Hasilnya berupa HasilPratinjau
 * yang baru disimpan oleh PenyimpanDataAgregat setelah Petugas menekan konfirmasi.
 */
class PembacaSheetDkb
{
    /** Kota Cimahi punya 15 kelurahan — dipakai sebagai penanda berhenti membaca. */
    public const JUMLAH_KELURAHAN = 15;

    /** Batas baris yang disusuri setelah header, pengaman terhadap sheet bermasalah. */
    private const MAKS_BARIS_DISUSURI = 400;

    /** Nilai galat rumus Excel yang mungkin tersimpan di sebuah sel. */
    private const KODE_GALAT_EXCEL = [
        '#REF!', '#VALUE!', '#N/A', '#DIV/0!', '#NAME?', '#NULL!', '#NUM!', '#SPILL!',
    ];

    private ValidatorImport $validator;

    public function __construct()
    {
        $this->validator = new ValidatorImport();
    }

    public function validator(): ValidatorImport
    {
        return $this->validator;
    }

    /**
     * Nama seluruh sheet di dalam berkas, tanpa memuat isinya.
     *
     * Dipakai dropdown di halaman Uji Coba Pemetaan. listWorksheetNames() hanya
     * membaca indeks berkas, jadi murah walau sheet-nya puluhan.
     *
     * @return array<int, string>
     */
    public function daftarSheet(string $path): array
    {
        return $this->pembuatReader($path)->listWorksheetNames($path);
    }

    /**
     * Baca seluruh sheet yang dipetakan sebuah profil.
     *
     * $tahun / $semester bila diisi akan MENANG atas hasil deteksi otomatis —
     * ini "override manual" pada Prinsip 4.
     */
    public function baca(
        string $path,
        string $profil,
        ?int $tahun = null,
        ?int $semester = null,
        ?string $namaBerkasAsli = null,
    ): HasilPratinjau {
        $hasil = new HasilPratinjau();

        $konfigurasi = KonfigurasiImport::query()
            ->aktif()
            ->profil($profil)
            ->orderBy('nama_sheet')
            ->orderBy('id')
            ->get()
            ->groupBy('nama_sheet');

        if ($konfigurasi->isEmpty()) {
            $this->validator->tidakAdaKonfigurasi($profil);

            return $this->bungkus($hasil);
        }

        $sheetTersedia = $this->daftarSheet($path);
        $sheetDiminta  = $konfigurasi->keys()->all();

        /*
         * Disdukcapil kadang mengganti nama sheet antar semester walau
         * isinya sama (mis. 'SHBKEL' jadi 'SHBKEL(rev)'). $sheetFisik
         * memetakan nama LOGIS (kunci $konfigurasi, dipakai di seluruh
         * kode lain) ke nama FISIK yang benar-benar ada di berkas ini —
         * nama sendiri bila ketemu, kalau tidak baru dicoba alias_sheet.
         *
         * @var array<string, string> nama logis => nama fisik di berkas
         */
        $sheetFisik = [];

        foreach ($sheetDiminta as $namaLogis) {
            if (in_array($namaLogis, $sheetTersedia, true)) {
                $sheetFisik[$namaLogis] = $namaLogis;

                continue;
            }

            $alias = $konfigurasi[$namaLogis]->first()->alias_sheet;

            if ($alias !== null && in_array($alias, $sheetTersedia, true)) {
                $sheetFisik[$namaLogis] = $alias;
                $this->validator->sheetDibacaLewatAlias($namaLogis, $alias);

                continue;
            }

            // Baik nama utama maupun alias tidak ada — sheet ini benar-benar hilang.
            $this->validator->sheetTidakDitemukan($namaLogis, $sheetTersedia);
        }

        if ($sheetFisik === []) {
            return $this->bungkus($hasil);
        }

        // Hanya sheet yang benar-benar ada yang dimuat. Meminta PhpSpreadsheet
        // memuat sheet yang tidak ada akan melempar exception dan mematikan
        // seluruh proses — padahal sheet lain masih bisa dibaca.
        $sheetDimuat = array_values(array_unique($sheetFisik));

        $kamusWilayah = DimWilayah::kamusPencocokan();
        // kelurahan() — "Kota Cimahi" sengaja tidak ikut di sini. Peta nama
        // ini dipakai pelaporan "kelurahan tidak lengkap" untuk sheet
        // per-kelurahan biasa; sheet rekap kota (ORIENTASI_KOTA) punya jalur
        // pelaporan sendiri lewat DimWilayah::idKota(), tidak lewat sini.
        $namaWilayah = DimWilayah::kelurahan()->pluck('nama_kelurahan', 'id')->all();

        $reader = $this->pembuatReader($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly($sheetDimuat);
        $spreadsheet = $reader->load($path);

        // Nama berkas ikut jadi kandidat periode ("… SEMESTER II 2025.xlsx").
        // Bukan sumber utama, tapi sangat berguna sebagai pembanding: kalau ia
        // bertentangan dengan judul di dalam sheet, salah satunya pasti basi.
        $kandidatPeriode = [];

        if ($namaBerkasAsli !== null && ($p = $this->bacaPeriode($namaBerkasAsli)) !== null) {
            $kandidatPeriode['nama berkas'] = $p;
        }

        foreach ($sheetFisik as $namaLogis => $namaFisik) {
            $baris = $this->keArray($spreadsheet->getSheetByName($namaFisik));

            if (($p = $this->periodeDariJudul($baris)) !== null) {
                $kandidatPeriode['sheet '.$namaLogis] = $p;
            }

            $this->bacaSatuSheet(
                $namaLogis,
                $baris,
                $konfigurasi[$namaLogis],
                $kamusWilayah,
                $namaWilayah,
                $hasil,
            );
        }

        // Lepas memori lebih awal: satu berkas DKB bisa puluhan MB saat dimuat.
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $this->putuskanPeriode($hasil, $kandidatPeriode);
        $this->tetapkanPeriode($hasil, $tahun, $semester);

        return $this->bungkus($hasil);
    }

    /**
     * Baca satu sheet tanpa menyimpan apa pun — mesin di balik "Uji Coba Pemetaan".
     *
     * Mengembalikan posisi tiap kolom yang terdeteksi supaya Petugas bisa melihat
     * sendiri apakah offset-nya sudah mendarat di kolom yang benar.
     *
     * @return array{kolom: array<int, array<string, mixed>>, baris_header: ?int,
     *               kolom_wilayah: ?int, cuplikan: array<int, array<string, mixed>>}
     */
    public function ujiPemetaan(string $path, string $namaSheet, string $profil, int $jumlahCuplikan = 5): array
    {
        $konfigurasi = KonfigurasiImport::query()
            ->profil($profil)
            ->where('nama_sheet', $namaSheet)
            ->orderBy('id')
            ->get();

        $reader = $this->pembuatReader($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$namaSheet]);
        $spreadsheet = $reader->load($path);

        $baris = $this->keArray($spreadsheet->getSheetByName($namaSheet));
        $spreadsheet->disconnectWorksheets();

        $pemeta   = new PemetaKolom($baris);
        $maks     = (int) ($konfigurasi->first()->baris_maks_pencarian_header ?? 12);
        $mulai    = (int) ($konfigurasi->first()->baris_mulai_pencarian_header ?? 0);
        $teksWil  = (string) ($konfigurasi->first()->teks_header_wilayah ?? 'Wilayah');
        $selWil   = $pemeta->selWilayah($teksWil, $maks, $mulai);

        $kolom = [];
        foreach ($konfigurasi as $k) {
            $indeks  = $pemeta->kolomUntuk($k->teks_header, $k->offset_kolom, $k->baris_maks_pencarian_header, $k->baris_mulai_pencarian_header);
            $kolom[] = [
                'label'        => $k->label,
                'teks_header'  => $k->teks_header,
                'offset_kolom' => $k->offset_kolom,
                'aktif'        => $k->aktif,
                'indeks'       => $indeks,
                'huruf'        => $indeks !== null ? PemetaKolom::hurufKolom($indeks) : null,
                // Isi sel di baris header pada kolom hasil hitung — inilah bukti
                // visual bahwa offset-nya mendarat di tempat yang benar.
                'isi_header'   => $indeks !== null && $selWil !== null
                    ? PemetaKolom::normalkan($baris[$selWil['baris']][$indeks] ?? null)
                    : null,
            ];
        }

        $cuplikan = [];
        if ($selWil !== null) {
            $mulai = $selWil['baris'] + 1;
            for ($b = $mulai; $b < count($baris) && count($cuplikan) < $jumlahCuplikan; $b++) {
                $nama = PemetaKolom::normalkan($baris[$b][$selWil['kolom']] ?? null);

                if ($nama === '') {
                    continue;
                }

                $nilai = [];
                foreach ($kolom as $k) {
                    $nilai[$k['label']] = $k['indeks'] !== null
                        ? ($baris[$b][$k['indeks']] ?? null)
                        : null;
                }

                $cuplikan[] = ['baris_excel' => $b + 1, 'wilayah' => $nama, 'nilai' => $nilai];
            }
        }

        return [
            'kolom'         => $kolom,
            'baris_header'  => $selWil['baris'] ?? null,
            'kolom_wilayah' => $selWil['kolom'] ?? null,
            'cuplikan'      => $cuplikan,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, KonfigurasiImport>  $konfigurasi
     * @param  array<string, int>  $kamusWilayah
     * @param  array<int, string>  $namaWilayah
     */
    private function bacaSatuSheet(
        string $namaSheet,
        array $baris,
        $konfigurasi,
        array $kamusWilayah,
        array $namaWilayah,
        HasilPratinjau $hasil,
    ): void {
        $pemeta  = new PemetaKolom($baris);
        $pertama = $konfigurasi->first();
        $maks    = (int) $pertama->baris_maks_pencarian_header;

        if ($pertama->transposed()) {
            $this->bacaSheetTerbalik($namaSheet, $baris, $pemeta, $konfigurasi, $kamusWilayah, $namaWilayah, $hasil, $maks);

            return;
        }

        if ($pertama->blok()) {
            $this->bacaSheetBlok($namaSheet, $baris, $pemeta, $konfigurasi, $kamusWilayah, $namaWilayah, $hasil, $maks, (int) $pertama->baris_mulai_pencarian_header);

            return;
        }

        if ($pertama->kota()) {
            $this->bacaSheetKota($namaSheet, $baris, $pemeta, $konfigurasi, $hasil);

            return;
        }

        if ($pertama->kecamatan()) {
            $this->bacaSheetKecamatan($namaSheet, $baris, $pemeta, $konfigurasi, $hasil);

            return;
        }

        // ---- Validasi struktur: SEKALI, sebelum baris mana pun dibaca ----
        $selWilayah = $pemeta->selWilayah($pertama->teks_header_wilayah, $maks, $pertama->baris_mulai_pencarian_header);

        if ($selWilayah === null) {
            $this->validator->kolomWilayahTidakDitemukan($namaSheet, $pertama->teks_header_wilayah, $maks);

            return;
        }

        $kolomTerpakai = [];
        foreach ($konfigurasi as $k) {
            $indeks = $pemeta->kolomUntuk($k->teks_header, $k->offset_kolom, $k->baris_maks_pencarian_header, $k->baris_mulai_pencarian_header);

            if ($indeks === null) {
                $this->validator->kolomTidakDitemukan(
                    $namaSheet,
                    $k->label,
                    $k->teks_header,
                    $k->baris_maks_pencarian_header,
                );

                continue;
            }

            $kolomTerpakai[] = ['konfigurasi' => $k, 'indeks' => $indeks];
        }

        if ($kolomTerpakai === []) {
            return;
        }

        // ---- Baru menyusuri baris data ----
        $sudahTerbaca = [];
        $selKosong    = [];
        $galatExcel   = [];
        $bukanAngka   = [];
        $totalAngka   = 0;
        $mulai        = $selWilayah['baris'] + 1;
        $batas        = min(count($baris), $mulai + self::MAKS_BARIS_DISUSURI);

        for ($b = $mulai; $b < $batas; $b++) {
            // PRINSIP 3: begitu 15 kelurahan didapat, berhenti. Di bawah tabel
            // utama beberapa sheet punya tabel pivot kedua dengan susunan kolom
            // berbeda — kalau terus dibaca, angkanya akan diambil dari kolom
            // yang salah tanpa ada error apa pun yang muncul.
            if (count($sudahTerbaca) >= self::JUMLAH_KELURAHAN) {
                break;
            }

            $namaMentah = AliasWilayah::normalkan($baris[$b][$selWilayah['kolom']] ?? null);

            if ($namaMentah === '') {
                continue;
            }

            $wilayahId = $kamusWilayah[$namaMentah] ?? null;

            // PRINSIP 2: baris yang namanya tidak dikenal dilewati diam-diam.
            // Inilah yang membuang baris subtotal kecamatan, total kota, dan
            // baris persentase tanpa perlu tahu di posisi mana mereka berada.
            if ($wilayahId === null || isset($sudahTerbaca[$wilayahId])) {
                continue;
            }

            $sudahTerbaca[$wilayahId] = true;
            $namaKelurahan            = $namaWilayah[$wilayahId] ?? $namaMentah;

            foreach ($kolomTerpakai as $kt) {
                $isiSel = $baris[$b][$kt['indeks']] ?? null;
                $label  = $kt['konfigurasi']->label;

                // Galat rumus Excel diperiksa lebih dulu supaya tidak tersamar
                // sebagai "bukan angka" biasa — penyebab dan perbaikannya beda.
                if ($kode = $this->kodeGalatExcel($isiSel)) {
                    $galatExcel[$kode][$namaKelurahan][] = $label;

                    continue;
                }

                $angka = $this->keAngka($isiSel);

                if ($angka === null) {
                    $bukanAngka[$label]['jumlah'] = ($bukanAngka[$label]['jumlah'] ?? 0) + 1;
                    $bukanAngka[$label]['isi']    = (string) $isiSel;
                    $bukanAngka[$label]['contoh'][] = $namaKelurahan;

                    continue;
                }

                if (PemetaKolom::normalkan($isiSel) === '') {
                    $selKosong[$label] = ($selKosong[$label] ?? 0) + 1;
                }

                $totalAngka += $angka;

                $hasil->tambahBaris(
                    $namaSheet,
                    $wilayahId,
                    $namaKelurahan,
                    $kt['konfigurasi']->jenis_indikator,
                    $kt['konfigurasi']->label,
                    $angka,
                );
            }
        }

        $terbaca      = count($sudahTerbaca);
        $barisTerakhir = isset($b) ? $b : $mulai;

        if ($terbaca < self::JUMLAH_KELURAHAN) {
            $this->validator->kelurahanTidakLengkap(
                $namaSheet,
                $terbaca,
                self::JUMLAH_KELURAHAN,
                $this->kelurahanHilang($namaWilayah, $sudahTerbaca),
            );
        }

        $this->laporkanSelBermasalah($namaSheet, $selKosong, $galatExcel, $bukanAngka);
        $this->laporkanGalatDiLuarDataUtama($namaSheet, $baris, $mulai, $barisTerakhir);

        $hasil->ringkasan[$namaSheet] = [
            'kelurahan_terbaca' => $terbaca,
            'jumlah_kolom'      => count($kolomTerpakai),
            'total_angka'       => $totalAngka,
            'baris_header'      => $selWilayah['baris'] + 1,
        ];
    }

    /**
     * Kelurahan di master yang tidak ketemu di sheet ini.
     *
     * @param  array<int, string>  $namaWilayah   id => nama kelurahan
     * @param  array<int, bool>    $sudahTerbaca  id kelurahan yang berhasil dibaca
     * @return array<int, string>
     */
    private function kelurahanHilang(array $namaWilayah, array $sudahTerbaca): array
    {
        $hilang = [];

        foreach ($namaWilayah as $id => $nama) {
            if (! isset($sudahTerbaca[$id])) {
                $hilang[] = $nama;
            }
        }

        sort($hilang);

        return $hilang;
    }

    /**
     * Hitung sel galat rumus yang ADA di sheet tapi TIDAK dibaca importer.
     *
     * Ini yang membedakan "berkasnya rusak" dari "berkasnya memang begitu".
     * Tanpa hitungan ini, satu-satunya kabar yang sampai ke Petugas adalah
     * kesunyian — lalu ketika ia membuka berkasnya sendiri dan melihat #REF!
     * bertaburan, ia tidak punya cara tahu bahwa importer tidak menyentuhnya.
     *
     * @param  array<int, array<int, mixed>>  $baris
     */
    private function laporkanGalatDiLuarDataUtama(string $namaSheet, array $baris, int $mulai, int $barisTerakhir): void
    {
        $perKode = [];

        foreach ($baris as $b => $row) {
            // Rentang yang benar-benar dibaca importer sudah dilaporkan terpisah
            if ($b >= $mulai && $b <= $barisTerakhir) {
                continue;
            }

            foreach ((array) $row as $sel) {
                if ($kode = $this->kodeGalatExcel($sel)) {
                    $perKode[$kode] = ($perKode[$kode] ?? 0) + 1;
                }
            }
        }

        foreach ($perKode as $kode => $jumlah) {
            $this->validator->selGalatDiLuarDataUtama(
                $namaSheet,
                $jumlah,
                (string) $kode,
                $mulai + 1,          // ditampilkan dalam penomoran baris Excel
                $barisTerakhir + 1,
            );
        }
    }

    /**
     * Ringkas sel bermasalah jadi pesan validator — dipakai kedua orientasi.
     *
     * @param  array<string, int>  $selKosong
     * @param  array<string, array<string, array<int, string>>>  $galatExcel  kode => kelurahan => label
     * @param  array<string, array{jumlah:int, isi:string, contoh:array<int,string>}>  $bukanAngka
     */
    private function laporkanSelBermasalah(
        string $namaSheet,
        array $selKosong,
        array $galatExcel,
        array $bukanAngka,
    ): void {
        foreach ($selKosong as $label => $jumlah) {
            $this->validator->nilaiKosong($namaSheet, (string) $label, $jumlah);
        }

        // Satu pesan per kode galat, memuat daftar kelurahan + kolom terdampak.
        // Sengaja tidak satu pesan per sel: satu kolom yang putus mengenai 15
        // kelurahan sekaligus, dan 15 pesan kembar menenggelamkan sisanya.
        foreach ($galatExcel as $kode => $perKelurahan) {
            $this->validator->nilaiGalatDiDataUtama($namaSheet, (string) $kode, $perKelurahan);
        }

        foreach ($bukanAngka as $label => $n) {
            $this->validator->nilaiTidakNumerik($namaSheet, (string) $label, $n['jumlah'], $n['contoh'], $n['isi']);
        }
    }

    /**
     * Baca sheet yang tersusun terbalik: baris = kategori, kolom = kelurahan.
     *
     * Sheet KelompokUmur disusun begini — 15 kelurahan dipecah jadi lima blok
     * tabel bersebelahan, tiap kelurahan memakai tiga kolom (L | P | TOTAL),
     * dan kelompok umurnya jadi baris. Semua blok memakai baris yang sama untuk
     * kelompok umur yang sama, jadi cukup sekali mencari barisnya lalu membaca
     * seluruh kolom kelurahan yang ditemukan di baris header.
     *
     * Blok rekap kecamatan dan kota ikut terbaca sebagai kolom, tapi rontok
     * sendiri karena judulnya ("CIMAHI SELATAN", "KOTA CIMAHI") tidak cocok
     * dengan nama kelurahan mana pun — mekanisme yang sama dengan Prinsip 2.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\KonfigurasiImport>  $konfigurasi
     * @param  array<string, int>  $kamusWilayah
     * @param  array<int, string>  $namaWilayah
     */
    private function bacaSheetTerbalik(
        string $namaSheet,
        array $baris,
        PemetaKolom $pemeta,
        $konfigurasi,
        array $kamusWilayah,
        array $namaWilayah,
        HasilPratinjau $hasil,
        int $maksBaris,
    ): void {
        $pertama = $konfigurasi->first();

        // ---- Kolom mana milik kelurahan mana ----
        $kolomWilayah = [];

        foreach (array_slice($baris, 0, $maksBaris, true) as $row) {
            foreach ((array) $row as $k => $sel) {
                $nama      = AliasWilayah::normalkan($sel);
                $wilayahId = $kamusWilayah[$nama] ?? null;

                if ($wilayahId !== null && ! isset($kolomWilayah[$wilayahId])) {
                    $kolomWilayah[$wilayahId] = (int) $k;
                }
            }
        }

        if ($kolomWilayah === []) {
            $this->validator->kolomWilayahTidakDitemukan($namaSheet, 'nama kelurahan', $maksBaris);

            return;
        }

        // ---- Baris mana milik label mana ----
        $barisLabel = [];

        /*
         * Label berupa angka bulat polos (mis. umur tunggal "50") TIDAK
         * dicari lewat cariBaris() biasa — dengan puluhan kolom data per
         * baris (satu per kelurahan), sangat mungkin ADA kelurahan lain yang
         * jumlah penduduknya di baris umur yang SALAH kebetulan sama persis
         * dengan angka umur yang sedang dicari (mis. baris umur 3 tahun
         * kebetulan punya sel bernilai "80" di suatu kelurahan → tertukar
         * dengan baris umur 80 tahun). Sebagai gantinya: baris "0" dicari
         * SEKALI sebagai jangkar, baris untuk umur N lainnya dihitung lewat
         * POSISI (jangkar + N) — aman karena umur tunggal selalu berurutan
         * tanpa celah, tidak bergantung pada pencarian teks sama sekali.
         */
        if ($pertama->kolomUrut()) {
            $jangkar = $pemeta->cariBaris('0');

            if ($jangkar === null) {
                $this->validator->kolomWilayahTidakDitemukan($namaSheet, '0 (baris jangkar)', $maksBaris);

                return;
            }

            foreach ($konfigurasi as $k) {
                $barisLabel[] = ['konfigurasi' => $k, 'baris' => $jangkar + (int) $k->teks_header];
            }
        } else {
            foreach ($konfigurasi as $k) {
                $indeks = $pemeta->cariBaris($k->teks_header);

                if ($indeks === null) {
                    $this->validator->kolomTidakDitemukan(
                        $namaSheet,
                        $k->label,
                        $k->teks_header,
                        $k->baris_maks_pencarian_header,
                    );

                    continue;
                }

                $barisLabel[] = ['konfigurasi' => $k, 'baris' => $indeks];
            }
        }

        if ($barisLabel === []) {
            return;
        }

        $galatExcel = [];
        $bukanAngka = [];
        $selKosong  = [];
        $totalAngka = 0;

        foreach ($kolomWilayah as $wilayahId => $kolom) {
            $namaKelurahan = $namaWilayah[$wilayahId] ?? '';

            foreach ($barisLabel as $bl) {
                $label  = $bl['konfigurasi']->label;
                $isiSel = $baris[$bl['baris']][$kolom + $bl['konfigurasi']->offset_kolom] ?? null;

                if ($kode = $this->kodeGalatExcel($isiSel)) {
                    $galatExcel[$kode][$namaKelurahan][] = $label;

                    continue;
                }

                $angka = $this->keAngka($isiSel);

                if ($angka === null) {
                    $bukanAngka[$label]['jumlah']   = ($bukanAngka[$label]['jumlah'] ?? 0) + 1;
                    $bukanAngka[$label]['isi']      = (string) $isiSel;
                    $bukanAngka[$label]['contoh'][] = $namaKelurahan;

                    continue;
                }

                if (PemetaKolom::normalkan($isiSel) === '') {
                    $selKosong[$label] = ($selKosong[$label] ?? 0) + 1;
                }

                $totalAngka += $angka;

                $hasil->tambahBaris(
                    $namaSheet,
                    $wilayahId,
                    $namaKelurahan,
                    $bl['konfigurasi']->jenis_indikator,
                    $label,
                    $angka,
                );
            }
        }

        $terbaca = count($kolomWilayah);

        if ($terbaca < self::JUMLAH_KELURAHAN) {
            $this->validator->kelurahanTidakLengkap(
                $namaSheet,
                $terbaca,
                self::JUMLAH_KELURAHAN,
                $this->kelurahanHilang($namaWilayah, $kolomWilayah),
            );
        }

        $this->laporkanSelBermasalah($namaSheet, $selKosong, $galatExcel, $bukanAngka);

        // Baris yang dibaca di orientasi terbalik tidak berurutan (satu baris per
        // label), jadi rentangnya diambil dari label paling atas sampai paling bawah.
        $barisDipakai = array_column($barisLabel, 'baris');
        $this->laporkanGalatDiLuarDataUtama($namaSheet, $baris, min($barisDipakai), max($barisDipakai));

        $hasil->ringkasan[$namaSheet] = [
            'kelurahan_terbaca' => $terbaca,
            'jumlah_kolom'      => count($barisLabel),
            'total_angka'       => $totalAngka,
            'baris_header'      => 0,
        ];
    }

    /**
     * Baca sheet yang tersusun sebagai BLOK: nama wilayah cuma ada di baris
     * PERTAMA tiap kelompok baris, sisanya kosong pada kolom wilayah.
     *
     * Sheet AngkatanKerja begini — tiap kelurahan memakai 10 baris (satu per
     * kelompok umur 15-19 s.d. 60-64), nama kelurahannya cuma ditulis di
     * baris pertama. Blok kota/kecamatan (KOTA CIMAHI, CIMAHI SELATAN, dst)
     * ikut berbentuk sama tapi rontok sendiri karena namanya tidak ada di
     * kamus kelurahan — mekanisme yang sama dengan Prinsip 2 di orientasi baris.
     *
     * Setiap label dijumlahkan (SUM) di seluruh baris satu blok, bukan
     * dibaca dari satu baris saja — angka Angkatan Kerja per kelurahan
     * memang totalnya semua kelompok umur, bukan cuma kelompok umur pertama.
     *
     * TPAK dihitung SETELAH penjumlahan, bukan dibaca langsung dari kolom
     * manapun: TPAK per kelurahan = total Angkatan Kerja ÷ total Jumlah
     * Penduduk Usia Kerja, sedangkan yang ada di berkas hanya TPAK PER
     * KELOMPOK UMUR (tidak bisa dirata-rata begitu saja karena besar
     * kelompok umurnya tidak sama).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\KonfigurasiImport>  $konfigurasi
     * @param  array<string, int>  $kamusWilayah
     * @param  array<int, string>  $namaWilayah
     */
    private function bacaSheetBlok(
        string $namaSheet,
        array $baris,
        PemetaKolom $pemeta,
        $konfigurasi,
        array $kamusWilayah,
        array $namaWilayah,
        HasilPratinjau $hasil,
        int $maksBaris,
        int $mulaiBaris,
    ): void {
        $pertama    = $konfigurasi->first();
        $selWilayah = $pemeta->selWilayah($pertama->teks_header_wilayah, $maksBaris, $mulaiBaris);

        if ($selWilayah === null) {
            $this->validator->kolomWilayahTidakDitemukan($namaSheet, $pertama->teks_header_wilayah, $maksBaris);

            return;
        }

        // Tiap label butuh DUA kolom (L dan P) yang dijumlahkan jadi satu
        // nilai — sheet ini tidak punya kolom L+P siap pakai seperti sheet lain.
        $kolomTerpakai = [];
        foreach ($konfigurasi as $k) {
            $kolomL = $pemeta->kolomUntuk($k->teks_header, $k->offset_kolom, $k->baris_maks_pencarian_header, $k->baris_mulai_pencarian_header);
            $kolomP = $pemeta->kolomUntuk($k->teks_header, $k->offset_kolom + 1, $k->baris_maks_pencarian_header, $k->baris_mulai_pencarian_header);

            if ($kolomL === null || $kolomP === null) {
                $this->validator->kolomTidakDitemukan($namaSheet, $k->label, $k->teks_header, $k->baris_maks_pencarian_header);

                continue;
            }

            $kolomTerpakai[] = ['konfigurasi' => $k, 'kolom' => [$kolomL, $kolomP]];
        }

        if ($kolomTerpakai === []) {
            return;
        }

        // label => jenis_indikator — dipetakan per label (BUKAN satu $jenis
        // bersama untuk seluruh sheet), supaya sheet 'blok' yang labelnya
        // berasal dari jenis_indikator berbeda-beda tetap benar tersimpan.
        $jenisPerLabel = [];
        foreach ($kolomTerpakai as $kt) {
            $jenisPerLabel[$kt['konfigurasi']->label] = $kt['konfigurasi']->jenis_indikator;
        }

        $sums       = []; // wilayahId => label => total
        $galatExcel = [];
        $bukanAngka = [];

        $wilayahSaatIni = null;
        $mulaiData      = $selWilayah['baris'] + 1;
        $batas          = min(count($baris), $mulaiData + self::MAKS_BARIS_DISUSURI);

        for ($b = $mulaiData; $b < $batas; $b++) {
            $namaMentah = AliasWilayah::normalkan($baris[$b][$selWilayah['kolom']] ?? null);

            // Baris berisi nama = awal blok baru. Baris kosong pada kolom
            // wilayah tetap milik blok yang sedang berjalan (carry-forward).
            if ($namaMentah !== '') {
                $wilayahSaatIni = $kamusWilayah[$namaMentah] ?? null;
            }

            if ($wilayahSaatIni === null) {
                continue;
            }

            $namaKelurahan = $namaWilayah[$wilayahSaatIni] ?? '';

            foreach ($kolomTerpakai as $kt) {
                $label = $kt['konfigurasi']->label;

                foreach ($kt['kolom'] as $indeks) {
                    $isiSel = $baris[$b][$indeks] ?? null;

                    if ($kode = $this->kodeGalatExcel($isiSel)) {
                        $galatExcel[$kode][$namaKelurahan][] = $label;

                        continue;
                    }

                    $angka = $this->keAngka($isiSel);

                    if ($angka === null) {
                        $bukanAngka[$label]['jumlah']   = ($bukanAngka[$label]['jumlah'] ?? 0) + 1;
                        $bukanAngka[$label]['isi']      = (string) $isiSel;
                        $bukanAngka[$label]['contoh'][] = $namaKelurahan;

                        continue;
                    }

                    $sums[$wilayahSaatIni][$label] = ($sums[$wilayahSaatIni][$label] ?? 0) + $angka;
                }
            }
        }

        $totalAngka = 0;

        foreach ($sums as $wilayahId => $perLabel) {
            $namaKelurahan = $namaWilayah[$wilayahId] ?? '';

            foreach ($perLabel as $label => $jumlah) {
                $hasil->tambahBaris($namaSheet, $wilayahId, $namaKelurahan, $jenisPerLabel[$label], $label, $jumlah);
                $totalAngka += $jumlah;
            }

            // TPAK turunan — lihat penjelasan di atas method ini.
            // data_agregat.jumlah adalah unsignedBigInteger (bilangan bulat
            // saja, sama seperti seluruh sheet rasio lain di aplikasi ini —
            // RasioJenisKelamin, RasioKetergantungan, dst juga kehilangan
            // desimalnya di keAngka()), jadi dibulatkan ke persen bulat.
            if (isset($perLabel['Angkatan Kerja'], $perLabel['Jumlah Penduduk Usia Kerja'])
                && $perLabel['Jumlah Penduduk Usia Kerja'] > 0) {
                $tpak = (int) round($perLabel['Angkatan Kerja'] / $perLabel['Jumlah Penduduk Usia Kerja'] * 100);
                $hasil->tambahBaris($namaSheet, $wilayahId, $namaKelurahan, $jenisPerLabel['Angkatan Kerja'], 'TPAK (%)', $tpak);
            }
        }

        $terbaca = count($sums);

        if ($terbaca < self::JUMLAH_KELURAHAN) {
            $this->validator->kelurahanTidakLengkap(
                $namaSheet,
                $terbaca,
                self::JUMLAH_KELURAHAN,
                $this->kelurahanHilang($namaWilayah, $sums),
            );
        }

        $this->laporkanSelBermasalah($namaSheet, [], $galatExcel, $bukanAngka);
        $this->laporkanGalatDiLuarDataUtama($namaSheet, $baris, $mulaiData, min($b, $batas) - 1);

        $hasil->ringkasan[$namaSheet] = [
            'kelurahan_terbaca' => $terbaca,
            'jumlah_kolom'      => count($kolomTerpakai),
            'total_angka'       => $totalAngka,
            'baris_header'      => $selWilayah['baris'] + 1,
        ];
    }

    /**
     * Baca sheet rekap SE-KOTA tanpa rincian kelurahan (mis. TerbitAktaKawin
     * — 12 baris bulan, tanpa kolom wilayah sama sekali). Setiap baris
     * konfigurasi dicari lewat cariBaris() (label = teks baris, mis. nama
     * bulan), nilainya diambil dari offset_kolom sebagai INDEKS KOLOM
     * LANGSUNG (bukan relatif ke kolom wilayah manapun — sheet ini memang
     * tidak punya dimensi wilayah, semuanya milik satu baris "Kota Cimahi").
     *
     * Tidak ada Prinsip 3 (berhenti di 15 kelurahan) di sini — jumlah baris
     * yang dibaca sama dengan jumlah label yang dikonfigurasi.
     *
     * @param  \Illuminate\Support\Collection<int, KonfigurasiImport>  $konfigurasi
     */
    private function bacaSheetKota(
        string $namaSheet,
        array $baris,
        PemetaKolom $pemeta,
        $konfigurasi,
        HasilPratinjau $hasil,
    ): void {
        $wilayahId = DimWilayah::idKota();

        if ($wilayahId === null) {
            $this->validator->tambahGalat(
                "Sheet '{$namaSheet}': baris wilayah \"Kota Cimahi\" belum ada di master wilayah (dim_wilayah).",
            );

            return;
        }

        $namaWilayah = 'Kota Cimahi';

        $galatExcel = [];
        $bukanAngka = [];
        $totalAngka = 0;
        $terbaca    = 0;

        foreach ($konfigurasi as $k) {
            $barisIndeks = $pemeta->cariBaris($k->teks_header, 60);

            if ($barisIndeks === null) {
                $this->validator->kolomTidakDitemukan($namaSheet, $k->label, $k->teks_header, $k->baris_maks_pencarian_header);

                continue;
            }

            /*
             * Kolom bisa dicari lewat teks header (bukan offset tetap) kalau
             * teks_header_kolom diisi — dipakai untuk sheet yang posisi
             * kolomnya berbeda antar-semester (mis. AngkatanKerjaPendidikan,
             * S1 punya kolom rincian L/P tambahan yang menggeser posisi
             * kolom totalnya, S2 tidak).
             */
            if ($k->teks_header_kolom) {
                $selKolom = $pemeta->cariSel($k->teks_header_kolom, 10);

                if ($selKolom === null) {
                    $this->validator->kolomTidakDitemukan($namaSheet, $k->label, $k->teks_header_kolom, 10);

                    continue;
                }

                // offset_kolom tetap berlaku DI ATAS hasil pencarian teks —
                // dipakai untuk header grup (mis. "Jenis Kelamin" lalu +0/+2
                // untuk mendarat di sub-kolom L/P), sama seperti kolomUntuk().
                $kolomIndeks = $selKolom['kolom'] + (int) $k->offset_kolom;
            } else {
                $kolomIndeks = $k->offset_kolom;
            }

            $isiSel = $baris[$barisIndeks][$kolomIndeks] ?? null;

            if ($kode = $this->kodeGalatExcel($isiSel)) {
                $galatExcel[$kode][$namaWilayah][] = $k->label;

                continue;
            }

            $angka = $this->keAngka($isiSel);

            if ($angka === null) {
                $bukanAngka[$k->label]['jumlah']   = ($bukanAngka[$k->label]['jumlah'] ?? 0) + 1;
                $bukanAngka[$k->label]['isi']      = (string) $isiSel;
                $bukanAngka[$k->label]['contoh'][] = $namaWilayah;

                continue;
            }

            $terbaca++;
            $totalAngka += $angka;
            $hasil->tambahBaris($namaSheet, $wilayahId, $namaWilayah, $k->jenis_indikator, $k->label, $angka);
        }

        $this->laporkanSelBermasalah($namaSheet, [], $galatExcel, $bukanAngka);

        $hasil->ringkasan[$namaSheet] = [
            'kelurahan_terbaca' => 1,
            'jumlah_kolom'      => $terbaca,
            'total_angka'       => $totalAngka,
            'baris_header'      => 0,
        ];
    }

    /**
     * Baca sheet yang datanya per-KECAMATAN (3 baris: Cimahi Selatan/Tengah/
     * Utara), bukan per-kelurahan (15 baris) atau se-Kota (1 baris).
     *
     * Dipakai untuk beberapa sheet dengan sedikit beda tata letak antar-blok:
     * Perkawinan_KU (Tabel 17) taruh baris header kelompok umur PERSIS di
     * title+1, sedangkan Agama_KelUmur_Kec/GolDar_KelUmur_Kec/Disabilitas_KU
     * menyelipkan satu baris sub-header ("NO|KECAMATAN|KELOMPOK UMUR") dulu
     * di title+1 baru kelompok umur di title+2. Karena itu baris header
     * kelompok umur DICARI (lewat teks "00-04", direntang title+1..title+4),
     * bukan diasumsikan tetap — baris 3 kecamatannya sendiri SELALU tepat
     * di bawahnya (+1/+2/+3), pola ini konsisten di semua sheet yang sudah
     * diverifikasi.
     *
     * teks_header konfigurasi = judul blok (persis, dipakai cariBaris() untuk
     * menemukan baris judulnya — harus UNIK per blok dalam satu sheet, mis.
     * "GOLONGAN DARAH A", bukan "KELOMPOK UMUR" yang berulang di semua blok).
     * offset_kolom = posisi kelompok umur (0 untuk "00-04", 1 untuk "05-09",
     * dst) — kolom sesungguhnya dicari lewat teks "00-04" (mekanisme sama
     * dengan ORIENTASI_KOLOM_URUT, tapi untuk KOLOM bukan baris), BUKAN
     * offset tetap, supaya tetap benar meski posisi kolomnya bergeser.
     *
     * @param  \Illuminate\Support\Collection<int, KonfigurasiImport>  $konfigurasi
     */
    private function bacaSheetKecamatan(
        string $namaSheet,
        array $baris,
        PemetaKolom $pemeta,
        $konfigurasi,
        HasilPratinjau $hasil,
    ): void {
        // Offset baris (dari baris header kelompok umur) tempat tiap kecamatan berada.
        $offsetKecamatan = ['Cimahi Selatan' => 1, 'Cimahi Tengah' => 2, 'Cimahi Utara' => 3];

        $galatExcel       = [];
        $bukanAngka       = [];
        $totalAngka       = 0;
        $terbaca          = 0;
        $kecamatanTerbaca = [];

        foreach ($konfigurasi->groupBy('teks_header') as $judulBlok => $itemBlok) {
            $barisJudul = $pemeta->cariBaris((string) $judulBlok, 60);

            if ($barisJudul === null) {
                $this->validator->kolomWilayahTidakDitemukan($namaSheet, (string) $judulBlok, 60);

                continue;
            }

            // Teks angkur kolom BISA disetel per sheet (mis. "4-6" untuk
            // kelompok umur sekolah), bawaannya "00-04" untuk sheet kelompok
            // umur 5-tahunan biasa.
            $teksAngkur = $itemBlok->first()->teks_header_kolom ?: '00-04';
            $selAngkur  = $pemeta->cariSel($teksAngkur, $barisJudul + 4, $barisJudul + 1);

            if ($selAngkur === null) {
                $this->validator->kolomWilayahTidakDitemukan(
                    $namaSheet,
                    "{$teksAngkur} (baris kelompok umur untuk blok '{$judulBlok}')",
                    $barisJudul + 4,
                );

                continue;
            }

            $barisAgeHeader = $selAngkur['baris'];
            $kolomAngkur    = $selAngkur['kolom'];

            foreach ($offsetKecamatan as $namaKecamatan => $offsetBaris) {
                $wilayahId = DimWilayah::idKecamatan($namaKecamatan);

                if ($wilayahId === null) {
                    $this->validator->tambahGalat(
                        "Sheet '{$namaSheet}': baris wilayah kecamatan \"{$namaKecamatan}\" belum ada di master wilayah (dim_wilayah).",
                    );

                    continue;
                }

                $barisData                       = $barisAgeHeader + $offsetBaris;
                $kecamatanTerbaca[$namaKecamatan] = true;

                foreach ($itemBlok as $k) {
                    $kolomIndeks = $kolomAngkur + (int) $k->offset_kolom;
                    $isiSel      = $baris[$barisData][$kolomIndeks] ?? null;

                    if ($kode = $this->kodeGalatExcel($isiSel)) {
                        $galatExcel[$kode][$namaKecamatan][] = $k->label;

                        continue;
                    }

                    $angka = $this->keAngka($isiSel);

                    if ($angka === null) {
                        $bukanAngka[$k->label]['jumlah']   = ($bukanAngka[$k->label]['jumlah'] ?? 0) + 1;
                        $bukanAngka[$k->label]['isi']      = (string) $isiSel;
                        $bukanAngka[$k->label]['contoh'][] = $namaKecamatan;

                        continue;
                    }

                    $terbaca++;
                    $totalAngka += $angka;
                    $hasil->tambahBaris($namaSheet, $wilayahId, $namaKecamatan, $k->jenis_indikator, $k->label, $angka);
                }
            }
        }

        $this->laporkanSelBermasalah($namaSheet, [], $galatExcel, $bukanAngka);

        $hasil->ringkasan[$namaSheet] = [
            'kelurahan_terbaca' => count($kecamatanTerbaca),
            'jumlah_kolom'      => $terbaca,
            'total_angka'       => $totalAngka,
            'baris_header'      => 0,
        ];
    }

    /**
     * Ubah satu sheet jadi array baris, TANPA menghitung ulang rumus apa pun.
     *
     * Ini menggantikan Worksheet::toArray(), dan alasannya penting:
     *
     * toArray() menghitung ulang setiap rumus. Berkas DKB penuh rumus lintas
     * sheet ("=Agama_JK!F8"), sementara importer hanya memuat sheet yang
     * dipetakan demi hemat memori. Sheet rujukannya jadi tidak ada, dan
     * PhpSpreadsheet mengembalikan '#REF!' — padahal berkasnya sehat dan
     * nilainya tersimpan rapi di dalam sel itu sendiri. Akibatnya seluruh
     * sheet Agama pernah dilaporkan rusak tanpa satu pun sel yang benar-benar
     * rusak.
     *
     * Yang dipakai di sini adalah nilai hasil hitung yang SUDAH DISIMPAN Excel
     * (getOldCalculatedValue). Selain kebal terhadap sheet yang tidak dimuat,
     * cara ini juga lebih cepat karena tidak ada kalkulasi sama sekali — dan
     * lebih setia pada berkas: yang masuk database persis angka yang dilihat
     * staf Disdukcapil di layarnya.
     *
     * Catatan: setReadDataOnly(true) TIDAK mencegah rumus dimuat di
     * PhpSpreadsheet 1.30 — ia hanya melewatkan gaya/format. Jangan mengira
     * bendera itu sudah cukup.
     *
     * @return array<int, array<int, mixed>>  indeks mulai 0 untuk baris & kolom
     */
    private function keArray(?Worksheet $sheet): array
    {
        if ($sheet === null) {
            return [];
        }

        $maksKolom = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $maksBaris = $sheet->getHighestRow();
        $hasil     = [];

        for ($r = 1; $r <= $maksBaris; $r++) {
            $isi = [];

            for ($k = 1; $k <= $maksKolom; $k++) {
                $ref = Coordinate::stringFromColumnIndex($k).$r;

                // cellExists() dulu: getCell() akan MEMBUAT sel yang belum ada
                // dan membengkakkan memori pada sheet yang lebarnya puluhan kolom.
                $isi[] = $sheet->cellExists($ref) ? $this->nilaiSel($sheet->getCell($ref)) : null;
            }

            $hasil[] = $isi;
        }

        return $hasil;
    }

    /**
     * Isi satu sel: nilai apa adanya, atau nilai tersimpan bila selnya rumus.
     *
     * Rumus tanpa nilai tersimpan dikembalikan sebagai teks rumusnya sendiri.
     * Itu disengaja: mengembalikan null akan membuatnya terbaca sebagai 0 dan
     * masuk database diam-diam, sedangkan sebagai teks ia akan tertangkap
     * validator sebagai "bukan angka" dan terlihat oleh Petugas.
     */
    private function nilaiSel(Cell $cell): mixed
    {
        $nilai = $cell->getValue();

        if ($nilai instanceof RichText) {
            return $nilai->getPlainText();
        }

        if (is_string($nilai) && str_starts_with($nilai, '=')) {
            return $cell->getOldCalculatedValue() ?? $nilai;
        }

        return $nilai;
    }

    /**
     * Kode galat rumus Excel bila selnya memang berisi galat, selain itu null.
     *
     * Berkas DKB nyata memuat sel #REF! dalam jumlah besar — rumus antar-berkas
     * yang tautannya putus saat berkas dikirim keluar dari komputer pembuatnya.
     * Sel seperti ini tidak boleh diperlakukan sebagai 0: nilainya bukan nol,
     * melainkan tidak ada.
     */
    private function kodeGalatExcel(mixed $nilai): ?string
    {
        if (! is_string($nilai)) {
            return null;
        }

        $teks = strtoupper(trim($nilai));

        return in_array($teks, self::KODE_GALAT_EXCEL, true) ? $teks : null;
    }

    /**
     * Ubah isi sel jadi bilangan bulat.
     *
     * Angka di file DKB bisa datang sebagai float (sel numerik asli) atau
     * sebagai teks berformat Indonesia ("1.234"). Titik dan koma dibuang
     * sebelum diperiksa karena keduanya di sini hanyalah pemisah ribuan —
     * data kependudukan berupa cacah jiwa, tidak pernah pecahan.
     *
     * Mengembalikan null hanya bila isinya benar-benar bukan angka; sel kosong
     * dan "-" dianggap 0 karena di berkas aslinya keduanya berarti nihil.
     */
    private function keAngka(mixed $nilai): ?int
    {
        if ($nilai === null) {
            return 0;
        }

        if (is_int($nilai) || is_float($nilai)) {
            return (int) round((float) $nilai);
        }

        $teks = trim(str_replace(["\xC2\xA0", ' ', '.', ','], '', (string) $nilai));

        if ($teks === '' || $teks === '-') {
            return 0;
        }

        return ctype_digit(ltrim($teks, '-')) ? (int) $teks : null;
    }

    /**
     * PRINSIP 4 — tebak tahun & semester dari judul di baris-baris awal sheet.
     *
     * @return array{semester:int, tahun:int}|null
     */
    private function periodeDariJudul(array $baris): ?array
    {
        $teks = '';

        foreach (array_slice($baris, 0, 5) as $row) {
            foreach ((array) $row as $sel) {
                $teks .= ' '.PemetaKolom::normalkan($sel);
            }
        }

        return $this->bacaPeriode($teks);
    }

    /**
     * Tarik "SEMESTER II … 2025" dari sepotong teks.
     *
     * Semester ditulis angka Romawi di berkas aslinya ("SEMESTER II TAHUN 2025").
     * Bentuk angka biasa ikut diterima karena penulisan judul tidak dijamin
     * konsisten antar semester, begitu pula nama berkas ("SEMESTER II 2025.xlsx").
     *
     * @return array{semester:int, tahun:int}|null
     */
    private function bacaPeriode(string $teks): ?array
    {
        $teks = PemetaKolom::normalkan($teks);

        if (! preg_match('/SEMESTER\s+(II|I|2|1)\b.*?(\d{4})/u', $teks, $cocok)) {
            return null;
        }

        return [
            'semester' => match ($cocok[1]) {
                'II', '2' => 2,
                default   => 1,
            },
            'tahun' => (int) $cocok[2],
        ];
    }

    /**
     * Pilih periode dari seluruh kandidat yang terkumpul.
     *
     * Satu berkas DKB nyata bisa memuat lebih dari satu periode: judul tabel
     * utamanya rusak jadi #REF!, sementara tabel pivot di sebelah kanan masih
     * menyimpan judul semester SEBELUMNYA. Kalau kandidat pertama langsung
     * dipakai — seperti versi sebelumnya — pratinjau akan menampilkan periode
     * lama dengan penanda hijau "terbaca otomatis", dan data satu semester
     * penuh bisa tertimpa di tempat yang salah tanpa ada tanda bahaya apa pun.
     *
     * Karena itu: sepakat = pakai, berbeda = menyerah dan Petugas yang menentukan.
     *
     * @param  array<string, array{semester:int, tahun:int}>  $kandidat
     */
    private function putuskanPeriode(HasilPratinjau $hasil, array $kandidat): void
    {
        if ($kandidat === []) {
            return;
        }

        // Kumpulkan suara: satu periode bisa disebut beberapa sheet sekaligus.
        $suara = [];

        foreach ($kandidat as $sumber => $p) {
            $kunci = "S{$p['semester']} {$p['tahun']}";

            $suara[$kunci]['periode'] = $p;
            $suara[$kunci]['sumber'][] = $sumber;
        }

        /*
         * Yang paling banyak disebut yang dipakai.
         *
         * Berkas DKB dirakit manual, dan judul satu-dua sheet sering ketinggalan
         * dari semester sebelumnya. Menyerah begitu ada satu ketidakcocokan
         * berarti memaksa Petugas mengisi periode manual setiap semester padahal
         * mayoritas sheet sudah menyebut jawabannya dengan benar. Yang penting
         * bukan menolak menebak, tapi menebak dengan suara terbanyak lalu
         * menunjukkan hasil hitungnya supaya Petugas bisa menilai sendiri.
         */
        uasort($suara, static fn (array $a, array $b): int => count($b['sumber']) <=> count($a['sumber']));

        $menang                           = reset($suara);
        $hasil->semester                  = $menang['periode']['semester'];
        $hasil->tahun                     = $menang['periode']['tahun'];
        $hasil->periodeTerdeteksiOtomatis = true;

        if (count($suara) === 1) {
            return;
        }

        // Bentrok: nilainya tetap diisi, tapi ditandai supaya pratinjau
        // menampilkannya sebagai dugaan yang perlu diperiksa, bukan kepastian.
        $hasil->periodeBentrok = true;

        $rincian = [];

        foreach ($suara as $kunci => $s) {
            $jumlah    = count($s['sumber']);
            $rincian[] = "{$kunci} — {$jumlah} sumber (".implode(', ', $s['sumber']).')';
        }

        $this->validator->periodeBentrok($rincian, "S{$hasil->semester} {$hasil->tahun}");
    }

    /** Nilai isian Petugas selalu menang atas hasil deteksi otomatis. */
    private function tetapkanPeriode(HasilPratinjau $hasil, ?int $tahun, ?int $semester): void
    {
        if ($tahun !== null) {
            $hasil->tahun                     = $tahun;
            $hasil->periodeTerdeteksiOtomatis = false;
        }

        if ($semester !== null) {
            $hasil->semester                  = $semester;
            $hasil->periodeTerdeteksiOtomatis = false;
        }

        if ($hasil->tahun === null || $hasil->semester === null) {
            $this->validator->periodeTidakTerdeteksi();
        }
    }

    private function bungkus(HasilPratinjau $hasil): HasilPratinjau
    {
        $hasil->galat      = $this->validator->galat();
        $hasil->peringatan = $this->validator->peringatan();
        $hasil->info       = $this->validator->info();

        return $hasil;
    }

    private function pembuatReader(string $path): IReader
    {
        return IOFactory::createReaderForFile($path);
    }
}
