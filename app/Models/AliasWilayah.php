<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AliasWilayah extends Model
{
    protected $table = 'alias_wilayah';

    protected $fillable = ['wilayah_id', 'nama_alias'];

    /** Dibakukan huruf kapital + spasi tunggal supaya varian ejaan sama-sama cocok. */
    public static function normalkan(?string $nama): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper((string) $nama)));
    }

    protected static function booted(): void
    {
        static::saving(fn (self $alias) => $alias->nama_alias = self::normalkan($alias->nama_alias));
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(DimWilayah::class, 'wilayah_id');
    }
}
