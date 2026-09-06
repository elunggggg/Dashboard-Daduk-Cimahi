<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DimKategori extends Model
{
    protected $table = 'dim_kategori';

    protected $fillable = ['jenis_indikator', 'label', 'aktif', 'urutan'];

    protected $casts = ['aktif' => 'boolean'];

    public function dataAgregat(): HasMany
    {
        return $this->hasMany(DataAgregat::class, 'kategori_id');
    }

    /** Dipakai seluruh query dashboard publik — indikator nonaktif tidak boleh tampil. */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
