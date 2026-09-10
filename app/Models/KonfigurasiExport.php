<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Satu baris = satu kolom pada berkas ekspor data agregat (Excel/PDF).
 *
 * Kumpulan `kunci` TETAP (lihat SUMBER) — cocok dengan field yang tersedia di
 * data_agregat + relasinya. Petugas hanya mengaktifkan/menonaktifkan,
 * mengurutkan, dan mengganti label lewat halaman "Konfigurasi Export".
 */
class KonfigurasiExport extends Model
{
    protected $table = 'konfigurasi_export';

    public const FORMAT_TEKS = 'teks';

    public const FORMAT_ANGKA = 'angka';

    protected $fillable = ['kunci', 'label', 'urutan', 'aktif', 'format'];

    protected $casts = ['aktif' => 'boolean', 'urutan' => 'integer'];

    /**
     * Sumber kolom yang dikenali. Nilai: label bawaan, format bawaan, dan
     * keterangan singkat untuk ditampilkan di UI. Urutan array = urutan
     * bawaan kolom pada berkas ekspor.
     *
     * @var array<string, array{label: string, format: string, keterangan: string}>
     */
    public const SUMBER = [
        'kode_wilayah' => ['label' => 'Kode Wilayah', 'format' => self::FORMAT_TEKS,  'keterangan' => 'Kode Kemendagri kelurahan (mis. 32.77.01.1001).'],
        'kecamatan'    => ['label' => 'Kecamatan',    'format' => self::FORMAT_TEKS,  'keterangan' => 'Nama kecamatan.'],
        'kelurahan'    => ['label' => 'Kelurahan',    'format' => self::FORMAT_TEKS,  'keterangan' => 'Nama kelurahan.'],
        'periode'      => ['label' => 'Periode',      'format' => self::FORMAT_TEKS,  'keterangan' => 'Label periode data (mis. S2 2025).'],
        'indikator'    => ['label' => 'Indikator',    'format' => self::FORMAT_TEKS,  'keterangan' => 'Kode jenis indikator (mis. agama, umur_tunggal).'],
        'kategori'     => ['label' => 'Kategori',     'format' => self::FORMAT_TEKS,  'keterangan' => 'Label kategori indikator (mis. Islam, Umur 17 Tahun).'],
        'laki'         => ['label' => 'Laki-laki',    'format' => self::FORMAT_ANGKA, 'keterangan' => 'Angka laki-laki. Hanya muncul untuk indikator yang punya rincian jenis kelamin.'],
        'perempuan'    => ['label' => 'Perempuan',    'format' => self::FORMAT_ANGKA, 'keterangan' => 'Angka perempuan. Hanya muncul untuk indikator yang punya rincian jenis kelamin.'],
        'jumlah'       => ['label' => 'Jumlah',       'format' => self::FORMAT_ANGKA, 'keterangan' => 'Nilai total kategori.'],
    ];

    /** Kolom yang HANYA relevan bila indikator terpilih punya rincian L/P. */
    public const KUNCI_RINCIAN_JK = ['laki', 'perempuan'];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /** Kolom aktif, sudah terurut — dipakai membangun berkas ekspor. */
    public static function terurut(): Collection
    {
        return static::query()->aktif()->orderBy('urutan')->orderBy('id')->get();
    }

    public function keteranganSumber(): string
    {
        return self::SUMBER[$this->kunci]['keterangan'] ?? '';
    }
}
