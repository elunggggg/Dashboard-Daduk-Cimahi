<?php

namespace App\Services\Import;

/**
 * Wadah hasil pembacaan file SEBELUM apa pun disimpan ke database.
 *
 * Objek inilah yang jadi isi halaman pratinjau (Prinsip 6). Ia sengaja hanya
 * menampung data — tidak menyentuh database dan tidak punya efek samping —
 * supaya alur "parse dulu, tampilkan, baru simpan kalau Petugas setuju" tidak
 * bisa tidak sengaja menulis apa pun di tengah jalan.
 */
class HasilPratinjau
{
    /**
     * Baris siap simpan, sudah dikunci per kombinasi wilayah+kategori.
     *
     * Bentuk tiap entri:
     *   ['sheet', 'wilayah_id', 'nama_kelurahan', 'jenis_indikator', 'label', 'jumlah']
     *
     * @var array<int, array<string, mixed>>
     */
    public array $baris = [];

    /**
     * Ringkasan per sheet untuk ditampilkan sebagai tabel di halaman pratinjau.
     *
     * Bentuk tiap entri (kunci = nama sheet):
     *   ['kelurahan_terbaca', 'jumlah_kolom', 'total_angka', 'galat', 'peringatan']
     *
     * @var array<string, array<string, mixed>>
     */
    public array $ringkasan = [];

    /**
     * Galat yang membuat import TIDAK boleh dilanjutkan.
     *
     * @var array<int, string>
     */
    public array $galat = [];

    /**
     * Hal yang patut dilihat Petugas tapi tidak membatalkan import
     * (mis. satu sheet hanya terbaca 14 dari 15 kelurahan).
     *
     * @var array<int, string>
     */
    public array $peringatan = [];

    /**
     * Catatan tanpa tuntutan tindakan — mis. sel #REF! di area pivot yang
     * memang tidak dibaca importer. Dipisah dari peringatan supaya halaman
     * pratinjau tidak menampilkan hal biasa dengan nada bahaya.
     *
     * @var array<int, string>
     */
    public array $info = [];

    public ?int $tahun = null;

    public ?int $semester = null;

    /** True bila tahun/semester berhasil dibaca dari berkas, bukan diisi Petugas. */
    public bool $periodeTerdeteksiOtomatis = false;

    /**
     * True bila berkas menyebut lebih dari satu periode dan yang dipakai adalah
     * suara terbanyak. Nilainya tetap terisi — ini menandai bahwa isian itu
     * dugaan yang perlu diperiksa, bukan kepastian.
     */
    public bool $periodeBentrok = false;

    /**
     * Kombinasi yang sudah punya angka di data_agregat.
     *
     * Dipakai halaman pratinjau untuk menawarkan pilihan timpa / lewati /
     * batalkan. Bentuk tiap entri:
     *   ['nama_kelurahan', 'jenis_indikator', 'label', 'jumlah_lama', 'jumlah_baru']
     *
     * @var array<int, array<string, mixed>>
     */
    public array $duplikat = [];

    public function tambahBaris(
        string $sheet,
        int $wilayahId,
        string $namaKelurahan,
        string $jenisIndikator,
        string $label,
        int $jumlah,
    ): void {
        $this->baris[] = [
            'sheet'           => $sheet,
            'wilayah_id'      => $wilayahId,
            'nama_kelurahan'  => $namaKelurahan,
            'jenis_indikator' => $jenisIndikator,
            'label'           => $label,
            'jumlah'          => $jumlah,
        ];
    }

    public function adaGalat(): bool
    {
        return $this->galat !== [];
    }

    public function adaDuplikat(): bool
    {
        return $this->duplikat !== [];
    }

    public function totalBaris(): int
    {
        return count($this->baris);
    }

    public function totalAngka(): int
    {
        return array_sum(array_column($this->baris, 'jumlah'));
    }

    /**
     * Sampel baris untuk ditampilkan di halaman pratinjau.
     *
     * Dibatasi karena satu file DKB penuh menghasilkan ribuan baris — merender
     * semuanya membuat halaman pratinjau berat tanpa menambah informasi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sampel(int $jumlah = 25): array
    {
        return array_slice($this->baris, 0, $jumlah);
    }

    /**
     * Bentuk ringkas untuk disimpan ke kolom catatan_hasil (JSON) di import_excels.
     *
     * @return array<string, mixed>
     */
    public function untukCatatan(): array
    {
        return [
            'tahun'        => $this->tahun,
            'semester'     => $this->semester,
            'total_baris'  => $this->totalBaris(),
            'total_angka'  => $this->totalAngka(),
            'per_sheet'    => $this->ringkasan,
            'peringatan'   => $this->peringatan,
        ];
    }
}
