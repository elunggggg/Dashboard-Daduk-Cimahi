<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan format berkas ekspor — baris tunggal (pola PengaturanTampilan).
 * Menampung nilai yang dulu hardcoded di EksporController / ekspor pdf.
 */
class PengaturanExport extends Model
{
    protected $table = 'pengaturan_export';

    protected $fillable = [
        'format_bawaan', 'batas_baris_pdf', 'orientasi_pdf', 'kop_judul', 'kop_subjudul',
    ];

    protected $casts = ['batas_baris_pdf' => 'integer'];

    /** Teks kop bawaan bila Petugas belum mengisinya sendiri. */
    public const KOP_JUDUL_BAWAAN = 'DADUK Cimahi — Dashboard Data Agregat Penduduk';

    public const KOP_SUBJUDUL_BAWAAN = 'Dinas Kependudukan dan Pencatatan Sipil Kota Cimahi';

    public static function current(): self
    {
        $row = static::query()->first();

        if ($row === null) {
            static::create([]);
            // Ambil ulang supaya nilai default kolom (excel/3000/landscape) ikut terisi.
            $row = static::query()->firstOrFail();
        }

        return $row;
    }

    public function getKopJudulTampilAttribute(): string
    {
        return trim((string) $this->kop_judul) ?: self::KOP_JUDUL_BAWAAN;
    }

    public function getKopSubjudulTampilAttribute(): string
    {
        return trim((string) $this->kop_subjudul) ?: self::KOP_SUBJUDUL_BAWAAN;
    }
}
