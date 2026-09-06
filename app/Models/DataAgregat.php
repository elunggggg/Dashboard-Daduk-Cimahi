<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataAgregat extends Model
{
    protected $table = 'data_agregat';

    protected $fillable = [
        'wilayah_id', 'waktu_id', 'kategori_id', 'jumlah',
        'import_id', 'created_by', 'updated_by',
    ];

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(DimWilayah::class, 'wilayah_id');
    }

    public function waktu(): BelongsTo
    {
        return $this->belongsTo(DimWaktu::class, 'waktu_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(DimKategori::class, 'kategori_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(ImportExcel::class, 'import_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
