<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PengaturanTampilan extends Model
{
    protected $table = 'pengaturan_tampilan';

    protected $fillable = ['logo_path', 'logo_instansi_path', 'latar_belakang_path', 'latar_belakang_body_path'];

    /** Baris tunggal — dibuat otomatis kalau belum ada. */
    public static function current(): self
    {
        return self::query()->first() ?? self::create([]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getLogoInstansiUrlAttribute(): ?string
    {
        return $this->logo_instansi_path ? Storage::disk('public')->url($this->logo_instansi_path) : null;
    }

    public function getLatarBelakangUrlAttribute(): ?string
    {
        return $this->latar_belakang_path ? Storage::disk('public')->url($this->latar_belakang_path) : null;
    }

    public function getLatarBelakangBodyUrlAttribute(): ?string
    {
        return $this->latar_belakang_body_path ? Storage::disk('public')->url($this->latar_belakang_body_path) : null;
    }
}
