<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class DimWaktu extends Model
{
    protected $table = 'dim_waktu';

    protected $fillable = ['tahun', 'semester', 'label'];

    public function dataAgregat(): HasMany
    {
        return $this->hasMany(DataAgregat::class, 'waktu_id');
    }

    /**
     * Dua periode yang PALING BARU DIUPLOAD (baris `dim_waktu` dibuat otomatis
     * saat import lewat firstOrCreate — jadi urutan `created_at`/`id` = urutan
     * upload, BUKAN urutan tahun/semester). Dipakai sebagai default box
     * "Perbandingan" (bandingkan data upload terbaru vs sebelumnya), beda dari
     * `getWaktuList()` yang diurutkan tahun/semester untuk ditampilkan di dropdown.
     *
     * @return Collection<int, self> terurut: index 0 = upload terbaru, index 1 = sebelumnya
     */
    public static function duaTerbaruDiupload(): Collection
    {
        return self::query()->orderByDesc('created_at')->orderByDesc('id')->take(2)->get();
    }
}
