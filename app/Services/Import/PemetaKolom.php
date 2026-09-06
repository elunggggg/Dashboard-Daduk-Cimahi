<?php

namespace App\Services\Import;

/**
 * Menerjemahkan konfigurasi_import menjadi indeks kolom yang nyata di sebuah sheet.
 *
 * INI PENERAPAN PRINSIP 1: kolom dicari lewat TEKS HEADER, tidak pernah lewat
 * indeks kolom yang dipatok di kode. Kalau semester depan Disdukcapil menyisipkan
 * satu kolom baru di tengah, importer tetap menemukan kolomnya sendiri karena yang
 * dicocokkan adalah tulisan di header, bukan posisinya.
 *
 * Kelas ini murni membaca — ia tidak menyentuh database dan tidak melempar
 * exception. Kegagalan menemukan kolom dilaporkan sebagai nilai null supaya
 * ValidatorImport yang menyusun pesan errornya secara utuh per sheet.
 */
class PemetaKolom
{
    /**
     * Panjang minimum teks_header agar boleh dicocokkan secara sebagian.
     *
     * Header golongan darah berupa "A", "B", "O" — kalau pencocokan sebagian
     * diizinkan untuk teks sependek itu, huruf "O" akan cocok dengan hampir
     * setiap kata di baris header dan kolomnya salah ambil. Ambang 4 karakter
     * menjaga fallback ini hanya berlaku untuk header yang cukup khas.
     */
    private const MIN_PANJANG_COCOK_SEBAGIAN = 4;

    /** @param array<int, array<int, mixed>> $baris Isi sheet apa adanya, indeks mulai 0 */
    public function __construct(private readonly array $baris)
    {
    }

    /**
     * Bakukan isi sel sebelum dibandingkan.
     *
     * Tiga gangguan yang nyata ada di file DKB dan semuanya membuat perbandingan
     * string mentah gagal: sel ber-wrap text (mengandung newline), spasi ganda
     * sisa perataan teks, dan beda huruf besar/kecil antar semester.
     */
    public static function normalkan(mixed $nilai): string
    {
        if ($nilai === null || is_array($nilai)) {
            return '';
        }

        $teks = str_replace(["\r", "\n", "\t", "\xC2\xA0"], ' ', (string) $nilai);
        $teks = preg_replace('/\s+/u', ' ', $teks);

        return mb_strtoupper(trim($teks));
    }

    /**
     * Cari sel berisi teks tertentu di N baris pertama.
     *
     * Strategi dua tahap, dan urutannya penting:
     *
     * 1. Cocok PERSIS. Ini yang diharapkan terjadi pada kondisi normal.
     * 2. Kalau tidak ada yang persis, baru cocok SEBAGIAN — menolong saat header
     *    di file ditulis "Islam (Jiwa)" atau "Wajib KTP *". Fallback ini ditolak
     *    bila hasilnya ambigu (lebih dari satu kolom cocok), karena menebak salah
     *    satu diam-diam jauh lebih berbahaya daripada melapor tidak ketemu:
     *    angkanya akan tetap masuk, hanya saja dari kolom yang keliru.
     *
     * @return array{baris:int, kolom:int}|null
     */
    public function cariSel(string $teksDicari, int $maksBaris, int $mulaiBaris = 0): ?array
    {
        $target = self::normalkan($teksDicari);

        if ($target === '') {
            return null;
        }

        $batas    = min($maksBaris, count($this->baris));
        $sebagian = [];

        for ($b = max(0, $mulaiBaris); $b < $batas; $b++) {
            foreach ((array) ($this->baris[$b] ?? []) as $k => $sel) {
                $isi = self::normalkan($sel);

                if ($isi === '') {
                    continue;
                }

                if ($isi === $target) {
                    return ['baris' => $b, 'kolom' => (int) $k];
                }

                if (mb_strlen($target) >= self::MIN_PANJANG_COCOK_SEBAGIAN && str_contains($isi, $target)) {
                    $sebagian[] = ['baris' => $b, 'kolom' => (int) $k];
                }
            }
        }

        // Tepat satu kandidat sebagian = aman dipakai. Nol atau lebih dari satu = menyerah.
        return count($sebagian) === 1 ? $sebagian[0] : null;
    }

    /**
     * Indeks kolom untuk satu baris konfigurasi, sudah termasuk offset.
     *
     * Offset menangani header bertingkat: yang dicari adalah teks GRUP-nya
     * (mis. "Pindah"), lalu digeser N kolom ke kanan untuk mendarat di
     * sub-kolom yang diinginkan (mis. "Jumlah" pada offset 2).
     */
    public function kolomUntuk(string $teksHeader, int $offset, int $maksBaris, int $mulaiBaris = 0): ?int
    {
        $sel = $this->cariSel($teksHeader, $maksBaris, $mulaiBaris);

        if ($sel === null) {
            return null;
        }

        $kolom = $sel['kolom'] + $offset;

        // Offset yang menembus batas kanan tabel berarti konfigurasinya salah,
        // bukan datanya. Dilaporkan sebagai tidak ketemu agar pesan errornya
        // sama informatifnya dengan kasus header tidak ada.
        return $kolom >= 0 ? $kolom : null;
    }

    /**
     * Posisi kolom nama wilayah sekaligus baris header tempat ia ditemukan.
     *
     * Baris header dipakai sebagai penanda mulai membaca data: baris data
     * pertama selalu ADA DI BAWAHNYA. Ini menggantikan asumsi "data mulai baris
     * ke-5" yang bikin importer lama patah tiap kali jumlah baris judul berubah.
     *
     * @return array{baris:int, kolom:int}|null
     */
    public function selWilayah(string $teksHeaderWilayah, int $maksBaris, int $mulaiBaris = 0): ?array
    {
        return $this->cariSel($teksHeaderWilayah, $maksBaris, $mulaiBaris);
    }

    /**
     * Kebalikan dari cariSel: cari BARIS yang memuat teks tertentu.
     *
     * Dipakai sheet bersusun terbalik (baris = kategori, kolom = kelurahan).
     * Yang dikembalikan hanya kecocokan PERTAMA, dan itu disengaja: sheet
     * KelompokUmur mengulang seluruh tabelnya di bawah dengan angka KUMULATIF.
     * Kalau kecocokan terakhir yang dipakai, angka yang masuk akan jauh lebih
     * besar tanpa satu pun error yang muncul.
     *
     * Pencarian dibatasi $maksKolom kolom pertama supaya tidak ikut menyentuh
     * blok rekap kecamatan/kota yang berada jauh di sebelah kanan.
     */
    public function cariBaris(string $teksDicari, int $maksKolom = 60): ?int
    {
        $target = self::normalkan($teksDicari);

        if ($target === '') {
            return null;
        }

        foreach ($this->baris as $b => $row) {
            foreach ((array) $row as $k => $sel) {
                if ((int) $k > $maksKolom) {
                    break;
                }

                if (self::normalkan($sel) === $target) {
                    return (int) $b;
                }
            }
        }

        return null;
    }

    /** Notasi kolom Excel (0 → A, 26 → AA) untuk ditampilkan di halaman Uji Coba Pemetaan. */
    public static function hurufKolom(int $indeks): string
    {
        $huruf = '';

        for ($n = $indeks; $n >= 0; $n = intdiv($n, 26) - 1) {
            $huruf = chr(65 + ($n % 26)).$huruf;
        }

        return $huruf;
    }
}
