<?php

namespace App\Services\Import;

/**
 * Pengumpul pesan galat & peringatan import.
 *
 * INI PENERAPAN PRINSIP 5. Importer versi lama memvalidasi struktur di dalam
 * loop baris, sehingga satu kolom yang tidak ketemu menghasilkan pesan yang
 * sama diulang 43 kali dan Petugas tidak bisa melihat masalah sebenarnya.
 *
 * Di sini validasi struktur dipanggil SEKALI per sheet, sebelum baris mana pun
 * dibaca. Setiap pesan wajib menyebut nama sheet dan label yang bermasalah,
 * plus apa yang sebenarnya dicari sistem — supaya jelas bagian pemetaan import
 * (KonfigurasiImportSeeder) mana yang perlu disesuaikan pengembang.
 */
class ValidatorImport
{
    /** @var array<int, string> */
    private array $galat = [];

    /** @var array<int, string> */
    private array $peringatan = [];

    /**
     * Catatan yang tidak menuntut tindakan apa pun — sekadar menjelaskan apa
     * yang importer LEWATI dengan sengaja.
     *
     * Dipisahkan dari peringatan karena pernah terbukti menyesatkan: sel #REF!
     * di area pivot dilaporkan dengan nada yang sama seperti data hilang,
     * sehingga Petugas mengira 7 indikator gagal diimpor padahal semuanya utuh.
     *
     * @var array<int, string>
     */
    private array $info = [];

    public function sheetTidakDitemukan(string $sheet, array $sheetTersedia): void
    {
        $contoh = array_slice($sheetTersedia, 0, 8);

        $this->galat[] = "Sheet '{$sheet}' tidak ada di dalam berkas. "
            ."Sheet yang tersedia antara lain: ".implode(', ', $contoh)
            .(count($sheetTersedia) > 8 ? ', …' : '').'. '
            .'Bila Disdukcapil mengganti nama sheet, pemetaan import perlu disesuaikan pengembang.';
    }

    /**
     * Sheet dibaca lewat nama alternatif (alias_sheet) karena nama utamanya
     * tidak ada di berkas ini. Bukan masalah — Disdukcapil memang kadang
     * mengganti nama sheet antar semester — tapi tetap dinyatakan supaya
     * Petugas tahu berkas ini punya nama sheet yang berbeda dari biasanya.
     */
    public function sheetDibacaLewatAlias(string $namaUtama, string $namaAlias): void
    {
        $this->info[] = "Sheet '{$namaUtama}' tidak ada di berkas ini, dibaca dari sheet "
            ."'{$namaAlias}' (nama alternatif) sebagai gantinya.";
    }

    public function kolomWilayahTidakDitemukan(string $sheet, string $teksDicari, int $maksBaris): void
    {
        $this->galat[] = "Sheet '{$sheet}': kolom nama wilayah tidak ditemukan "
            ."(mencari teks header '{$teksDicari}' di baris 1–{$maksBaris}). "
            .'Tanpa kolom ini tidak ada baris yang bisa dicocokkan ke kelurahan.';
    }

    public function kolomTidakDitemukan(string $sheet, string $label, string $teksDicari, int $maksBaris): void
    {
        $this->galat[] = "Sheet '{$sheet}': kolom untuk label '{$label}' tidak ditemukan "
            ."(mencari teks header '{$teksDicari}' di baris 1–{$maksBaris}).";
    }

    public function kolomAmbigu(string $sheet, string $label, string $teksDicari): void
    {
        $this->galat[] = "Sheet '{$sheet}': teks header '{$teksDicari}' untuk label '{$label}' "
            .'cocok dengan lebih dari satu kolom, sehingga tidak bisa dipastikan mana yang benar. '
            .'Teks header pada pemetaan import perlu dibuat lebih spesifik oleh pengembang.';
    }

    /**
     * Kelurahan tidak lengkap = peringatan, bukan galat.
     *
     * Sebagian sheet DKB memang tidak memuat semua kelurahan (mis. sheet yang
     * hanya mendata kasus tertentu). Memblokir import karenanya akan menolak
     * data yang sebetulnya sah, jadi Petugas cukup diberi tahu dan ia yang
     * memutuskan.
     */
    /** @param  array<int, string>  $namaHilang  kelurahan di master yang tidak ketemu di sheet */
    public function kelurahanTidakLengkap(string $sheet, int $terbaca, int $seharusnya, array $namaHilang = []): void
    {
        $pesan = "Sheet '{$sheet}': hanya {$terbaca} dari {$seharusnya} kelurahan terbaca.";

        // Menyebut namanya membuat Petugas bisa langsung membuka berkasnya dan
        // membandingkan ejaan, tanpa menebak kelurahan mana yang bermasalah.
        if ($namaHilang !== []) {
            $pesan .= ' Tidak ditemukan: '.implode(', ', $namaHilang).'.';
        }

        $this->peringatan[] = $pesan
            .' Kemungkinan penyebabnya ejaan nama di berkas berbeda dari master wilayah, atau baris'
            .' datanya terpotong. Bila ejaannya yang berbeda, tambahkan sebagai alias di menu Kelola Wilayah.';
    }

    public function nilaiKosong(string $sheet, string $label, int $jumlahSel): void
    {
        $this->peringatan[] = "Sheet '{$sheet}': {$jumlahSel} sel kosong pada label '{$label}' dibaca sebagai 0.";
    }

    /**
     * Dilaporkan SEKALI per label, bukan per sel.
     *
     * Kalau satu kolom meleset, semua kelurahan di kolom itu ikut salah — 15
     * pesan yang isinya sama hanya menutupi masalah lain di bawahnya.
     *
     * @param  array<int, string>  $contohKelurahan
     */
    public function nilaiTidakNumerik(string $sheet, string $label, int $jumlahSel, array $contohKelurahan, string $contohIsi): void
    {
        $contoh = implode(', ', array_slice($contohKelurahan, 0, 3));

        $this->galat[] = "Sheet '{$sheet}': {$jumlahSel} nilai pada label '{$label}' bukan angka "
            ."(mis. '{$contohIsi}' di {$contoh}). Periksa apakah offset kolomnya sudah tepat.";
    }

    /**
     * Sel berisi galat rumus Excel (#REF!, #VALUE!, #N/A, …) — PERINGATAN, bukan galat.
     *
     * Dibedakan dari "bukan angka" biasa karena penyebab, akibat, dan
     * perbaikannya semuanya berbeda:
     *
     * - "bukan angka" biasanya berarti offset kolomnya meleset, jadi ada risiko
     *   angka salah kolom ikut masuk → wajib memblokir.
     * - #REF! berarti rumusnya sudah rusak di BERKAS SUMBER. Tidak ada angka
     *   yang bisa salah masuk: selnya dilewati begitu saja. Memblokir seluruh
     *   berkas karenanya hanya akan menyandera puluhan kolom lain yang sehat,
     *   padahal file DKB nyata hampir selalu memuat sisa tautan yang putus.
     *
     * Yang penting justru dinyatakan terang-terangan: labelnya TIDAK diimpor,
     * dan itu bukan sama dengan nilai nol.
     */
    /**
     * Galat rumus di KOLOM DATA UTAMA — kondisi paling serius dari tiga tingkat.
     *
     * Tidak memblokir import: sel yang rusak dilewati, sel lain tetap masuk,
     * dan tidak ada satu angka pun yang bisa salah tempat. Yang penting
     * dinyatakan tegas adalah akibatnya — angka untuk kelurahan yang disebut
     * memang TIDAK ADA di database setelah import ini.
     *
     * @param  array<string, array<int, string>>  $perKelurahan  nama kelurahan => daftar label
     */
    public function nilaiGalatDiDataUtama(string $sheet, string $kode, array $perKelurahan): void
    {
        $rincian = [];

        foreach ($perKelurahan as $kelurahan => $label) {
            $rincian[] = $kelurahan.' ('.implode(', ', $label).')';
        }

        $jumlah = count($perKelurahan);

        $this->peringatan[] = "Sheet '{$sheet}': {$jumlah} kelurahan punya nilai galat rumus ({$kode}) "
            .'di kolom data utama, jadi angkanya TIDAK diunggah — bukan disimpan sebagai 0. Terdampak: '
            .implode('; ', $rincian).'. Perbaiki rumusnya di Excel (atau salin-tempel hasilnya sebagai '
            .'nilai) lalu unggah ulang, atau isi lewat Mode B untuk kolom yang bersangkutan.';
    }

    /**
     * Galat rumus di LUAR data utama — tingkat paling ringan, cuma pemberitahuan.
     *
     * Berkas DKB nyata selalu memuat tabel pivot, baris subtotal, dan catatan
     * kaki yang rumusnya sudah putus. Importer berhenti setelah 15 kelurahan
     * (Prinsip 3) sehingga area itu tidak pernah dibaca — dan justru itu yang
     * perlu dinyatakan, supaya Petugas tidak mengira ada data yang hilang.
     */
    public function selGalatDiLuarDataUtama(string $sheet, int $jumlahSel, string $kode, int $barisAwal, int $barisAkhir): void
    {
        $this->info[] = "Sheet '{$sheet}': {$jumlahSel} sel berisi galat rumus ({$kode}) di area di luar "
            ."tabel utama — tabel pivot, baris subtotal, atau catatan kaki. Sistem hanya membaca baris "
            ."{$barisAwal}–{$barisAkhir}, jadi sel itu tidak dipakai dan data utama tidak terpengaruh.";
    }

    /**
     * Judul di berkas menyebut lebih dari satu periode.
     *
     * Terjadi karena DKB dirakit manual: judul satu-dua sheet masih tertinggal
     * di semester sebelumnya. Yang dipakai adalah periode dengan sumber
     * terbanyak, dan hitungan suaranya ikut ditampilkan supaya Petugas bisa
     * menilai sendiri apakah dugaan itu masuk akal sebelum menyimpan.
     *
     * @param  array<int, string>  $rincianSuara
     */
    public function periodeBentrok(array $rincianSuara, string $dipakai): void
    {
        $this->peringatan[] = 'Periode di dalam berkas tidak konsisten — '.implode(' · ', $rincianSuara).'. '
            ."Yang dipakai: {$dipakai} (suara terbanyak). Periksa isian Tahun dan Semester di formulir, "
            .'ubah bila dugaan ini keliru.';
    }

    public function tidakAdaKonfigurasi(string $profil): void
    {
        $this->galat[] = "Profil '{$profil}' tidak punya satu pun pemetaan aktif. "
            .'Pemetaan import untuk profil ini belum tersedia — hubungi pengembang.';
    }

    public function periodeTidakTerdeteksi(): void
    {
        $this->peringatan[] = 'Tahun dan semester tidak terbaca otomatis dari judul sheet. '
            .'Pastikan nilai yang tampil di formulir sudah benar sebelum menyimpan.';
    }

    public function tambahGalat(string $pesan): void
    {
        $this->galat[] = $pesan;
    }

    /** @return array<int, string> */
    public function galat(): array
    {
        return array_values(array_unique($this->galat));
    }

    /** @return array<int, string> */
    public function peringatan(): array
    {
        return array_values(array_unique($this->peringatan));
    }

    /** @return array<int, string> */
    public function info(): array
    {
        return array_values(array_unique($this->info));
    }

    public function adaGalat(): bool
    {
        return $this->galat !== [];
    }
}
