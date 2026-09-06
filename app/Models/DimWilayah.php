<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimWilayah extends Model
{
    protected $table = 'dim_wilayah';

    protected $fillable = ['kode_kemendagri', 'kode_kecamatan', 'nama_kelurahan', 'nama_kecamatan', 'luas_km2', 'is_kota', 'is_kecamatan'];

    protected $casts = ['is_kota' => 'boolean', 'is_kecamatan' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $wilayah) {
            // kode_kecamatan diturunkan otomatis dari kode_kemendagri
            // (format xx.xx.xx.xxxx — kecamatan = 3 segmen pertama).
            $bagian = explode('.', (string) $wilayah->kode_kemendagri);
            if (count($bagian) === 4) {
                $wilayah->kode_kecamatan = implode('.', array_slice($bagian, 0, 3));
            }
        });
    }

    public function dataAgregat(): HasMany
    {
        return $this->hasMany(DataAgregat::class, 'wilayah_id');
    }

    public function alias(): HasMany
    {
        return $this->hasMany(AliasWilayah::class, 'wilayah_id');
    }

    /**
     * Kamus nama→id untuk mencocokkan sel wilayah di berkas Excel: nama resmi
     * kelurahan + seluruh alias-nya, sama-sama dibakukan lewat
     * AliasWilayah::normalkan() supaya ejaan yang berbeda tetap cocok.
     *
     * @return array<string, int>
     */
    public static function kamusPencocokan(): array
    {
        $kamus = [];

        // SENGAJA kelurahan() — baris "Kota Cimahi" dan baris kecamatan
        // ("Cimahi Selatan" dkk) harus TIDAK dikenali di sini. Banyak sheet
        // per-kelurahan punya baris rekap kota/kecamatan di bawah 15 baris
        // kelurahan; kalau ikut masuk kamus ini, baris rekap itu akan ikut
        // kebaca dan mencemari total (data dobel). Sheet yang MEMANG rekap
        // kota/kecamatan memakai ORIENTASI_KOTA/ORIENTASI_KECAMATAN +
        // DimWilayah::idKota()/idKecamatan(), jalur terpisah dari kamus ini.
        foreach (self::query()->kelurahan()->with('alias')->get() as $wilayah) {
            $kamus[AliasWilayah::normalkan($wilayah->nama_kelurahan)] = $wilayah->id;

            foreach ($wilayah->alias as $alias) {
                $kamus[$alias->nama_alias] = $wilayah->id;
            }
        }

        return $kamus;
    }

    /**
     * Id baris "Kota Cimahi" — dipakai satu-satunya oleh sheet berorientasi
     * ORIENTASI_KOTA (rekap se-kota tanpa rincian kelurahan). SENGAJA tidak
     * lewat kamusPencocokan() — lihat catatan di migrasi is_kota.
     */
    public static function idKota(): ?int
    {
        return self::where('is_kota', true)->value('id');
    }

    /**
     * Id baris kecamatan ("Cimahi Selatan"/"Cimahi Tengah"/"Cimahi Utara") —
     * dipakai satu-satunya oleh sheet berorientasi ORIENTASI_KECAMATAN (rekap
     * per kecamatan tanpa rincian kelurahan). SENGAJA tidak lewat
     * kamusPencocokan() — lihat catatan di migrasi is_kecamatan.
     */
    public static function idKecamatan(string $namaKecamatan): ?int
    {
        return self::where('is_kecamatan', true)->where('nama_kecamatan', $namaKecamatan)->value('id');
    }

    /** 15 kelurahan asli, TANPA baris "Kota Cimahi"/kecamatan. Dipakai dropdown filter & Peta. */
    public static function scopeKelurahan($query)
    {
        return $query->where('is_kota', false)->where('is_kecamatan', false);
    }
}
