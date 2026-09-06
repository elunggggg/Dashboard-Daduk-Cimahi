<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KonfigurasiImport extends Model
{
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function scopeProfil(Builder $query, string $namaProfil): Builder
    {
        return $query->where('nama_profil', $namaProfil);
    }

    /** True bila sheet ini bersusun terbalik (kolom = kelurahan, baris = label). */
    public function transposed(): bool
    {
        return in_array($this->orientasi, [self::ORIENTASI_KOLOM, self::ORIENTASI_KOLOM_URUT], true);
    }

    /**
     * True bila baris labelnya berupa URUTAN ANGKA BERSAMBUNG (mis. umur
     * tunggal 0-99) yang harus dicari lewat POSISI (baris_anchor + N), bukan
     * lewat cariBaris() teks biasa. Label berupa angka bulat polos ("50")
     * sangat rawan collision dengan nilai data penduduk yang kebetulan sama
     * persis di baris lain — lihat catatan panjang di
     * PembacaSheetDkb::bacaSheetTerbalik().
     */
    public function kolomUrut(): bool
    {
        return $this->orientasi === self::ORIENTASI_KOLOM_URUT;
    }

    /**
     * True bila sheet ini tersusun sebagai BLOK: nama wilayah hanya muncul di
     * baris PERTAMA tiap kelompok baris (mis. 10 baris kelompok umur per
     * kelurahan), baris-baris sisanya kosong pada kolom wilayah. Nilainya
     * harus DIJUMLAHKAN dulu di seluruh baris satu blok sebelum disimpan.
     */
    public function blok(): bool
    {
        return $this->orientasi === self::ORIENTASI_BLOK;
    }

    /**
     * True bila sheet ini rekap SE-KOTA tanpa rincian kelurahan (mis.
     * Pekerjaan, TerbitAktaKawin). Seluruh nilainya masuk ke SATU baris
     * wilayah "Kota Cimahi" (DimWilayah::idKota()), bukan 15 kelurahan.
     */
    public function kota(): bool
    {
        return $this->orientasi === self::ORIENTASI_KOTA;
    }

    /**
     * True bila sheet ini rekap PER-KECAMATAN (3 baris: Cimahi Selatan/
     * Tengah/Utara), bukan per-kelurahan (15 baris) atau se-Kota (1 baris).
     * Mis. Perkawinan_KU. Lihat PembacaSheetDkb::bacaSheetKecamatan().
     */
    public function kecamatan(): bool
    {
        return $this->orientasi === self::ORIENTASI_KECAMATAN;
    }

    protected $table = 'konfigurasi_import';

    public const PROFIL_BAWAAN = 'Profil Standar DKB';

    public const ORIENTASI_BARIS = 'baris';

    public const ORIENTASI_KOLOM = 'kolom';

    public const ORIENTASI_BLOK = 'blok';

    public const ORIENTASI_KOTA = 'kota';

    public const ORIENTASI_KOLOM_URUT = 'kolom_urut';

    public const ORIENTASI_KECAMATAN = 'kecamatan';

    protected $fillable = [
        'nama_profil', 'nama_sheet', 'alias_sheet', 'jenis_indikator', 'label',
        'teks_header', 'offset_kolom', 'teks_header_kolom', 'orientasi',
        'teks_header_wilayah', 'baris_maks_pencarian_header',
        'baris_mulai_pencarian_header', 'aktif',
    ];

    protected $casts = ['aktif' => 'boolean'];

    public static function daftarProfil(): \Illuminate\Support\Collection
    {
        return self::query()->distinct()->orderBy('nama_profil')->pluck('nama_profil');
    }
}
