<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DimKategori extends Model
{
    protected $table = 'dim_kategori';

    protected $fillable = ['jenis_indikator', 'label', 'aktif', 'urutan'];

    protected $casts = ['aktif' => 'boolean'];

    /**
     * Versi terbaca dari `jenis_indikator` untuk ditampilkan ke Petugas —
     * kode `ak_pendidikan_bekerja` jadi "Ak Pendidikan Bekerja". Kodenya
     * sendiri tetap dipakai sebagai kunci pengelompokan di seluruh query.
     */
    public function getJenisTerbacaAttribute(): string
    {
        return Str::headline((string) $this->jenis_indikator);
    }

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
