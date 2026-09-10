<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Satu baris = satu ELEMEN pada berkas unduhan data agregat, disesuaikan
 * dengan berkas Excel gaya DKB (lihat DkbSheetExport). Sebagian elemen juga
 * dipakai unduhan PDF (kolom identitas PDF sendiri tetap baku).
 *
 * Petugas mengaktifkan/menonaktifkan elemen (kecuali yang WAJIB) dan mengganti
 * teks label-nya lewat halaman "Konfigurasi Unduh".
 */
class KonfigurasiExport extends Model
{
    protected $table = 'konfigurasi_export';

    protected $fillable = ['kunci', 'label', 'urutan', 'aktif'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer'];

    /**
     * Elemen berkas yang dikenali. `lingkup`: 'excel' (hanya Excel gaya DKB),
     * 'dua' (Excel + PDF). `wajib`: tidak bisa dinonaktifkan.
     *
     * @var array<string, array{label: string, wajib: bool, lingkup: string, keterangan: string}>
     */
    public const SUMBER = [
        'no' => [
            'label' => 'No', 'wajib' => false, 'lingkup' => 'excel',
            'keterangan' => 'Kolom nomor urut kelurahan pada berkas Excel.',
        ],
        'wilayah' => [
            'label' => 'Wilayah', 'wajib' => true, 'lingkup' => 'excel',
            'keterangan' => 'Judul kolom nama wilayah pada berkas Excel.',
        ],
        'laki' => [
            'label' => 'Laki-laki', 'wajib' => false, 'lingkup' => 'dua',
            'keterangan' => 'Sub-kolom laki-laki untuk indikator ber-rincian jenis kelamin. Nonaktif → hanya kolom Jumlah yang ditampilkan.',
        ],
        'perempuan' => [
            'label' => 'Perempuan', 'wajib' => false, 'lingkup' => 'dua',
            'keterangan' => 'Sub-kolom perempuan untuk indikator ber-rincian jenis kelamin.',
        ],
        'jumlah' => [
            'label' => 'Jumlah', 'wajib' => true, 'lingkup' => 'dua',
            'keterangan' => 'Judul kolom nilai total per kategori.',
        ],
        'jumlah_seluruhnya' => [
            'label' => 'Jumlah Seluruhnya', 'wajib' => false, 'lingkup' => 'excel',
            'keterangan' => 'Kolom grand-total per baris pada berkas Excel. Otomatis dilewati untuk indikator rasio/rata-rata.',
        ],
        'subtotal_kecamatan' => [
            'label' => 'Subtotal per Kecamatan', 'wajib' => false, 'lingkup' => 'excel',
            'keterangan' => 'Baris subtotal tiap kecamatan pada berkas Excel. Otomatis dilewati untuk indikator rasio/rata-rata.',
        ],
        'total_kota' => [
            'label' => 'KOTA CIMAHI', 'wajib' => false, 'lingkup' => 'excel',
            'keterangan' => 'Baris total se-Kota pada berkas Excel. Label ini dipakai apa adanya sebagai teks baris. Otomatis dilewati untuk indikator rasio/rata-rata.',
        ],
    ];

    /** @var array<string, self>|null cache per-request */
    private static ?array $peta = null;

    protected static function booted(): void
    {
        // Tulis apa pun ke tabel ini → cache dianggap basi.
        static::saved(fn () => self::$peta = null);
        static::deleted(fn () => self::$peta = null);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public function wajib(): bool
    {
        return (bool) (self::SUMBER[$this->kunci]['wajib'] ?? false);
    }

    public function keteranganSumber(): string
    {
        return self::SUMBER[$this->kunci]['keterangan'] ?? '';
    }

    /** Semua elemen, terurut untuk daftar di UI. */
    public static function terurut(): Collection
    {
        return static::query()->orderBy('urutan')->orderBy('id')->get();
    }

    private static function peta(): array
    {
        return self::$peta ??= static::query()->get()->keyBy('kunci')->all();
    }

    /** Elemen aktif? (WAJIB selalu dianggap aktif; belum tercatat → pakai bawaan). */
    public static function elemenAktif(string $kunci): bool
    {
        if (self::SUMBER[$kunci]['wajib'] ?? false) {
            return true;
        }

        $baris = self::peta()[$kunci] ?? null;

        return $baris ? (bool) $baris->aktif : true;
    }

    /** Teks label tersimpan, atau bawaan bila belum diubah/belum tercatat. */
    public static function labelElemen(string $kunci): string
    {
        $baris = self::peta()[$kunci] ?? null;

        return $baris && trim((string) $baris->label) !== ''
            ? $baris->label
            : (self::SUMBER[$kunci]['label'] ?? $kunci);
    }
}
