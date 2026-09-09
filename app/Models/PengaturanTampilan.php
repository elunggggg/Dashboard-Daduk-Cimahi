<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PengaturanTampilan extends Model
{
    protected $table = 'pengaturan_tampilan';

    protected $fillable = [
        'nama_sistem', 'nama_instansi',
        'logo_path', 'logo_instansi_path', 'latar_belakang_path', 'latar_belakang_body_path',
    ];

    /** Teks bawaan bila Petugas belum mengisi nama sistem/instansi sendiri. */
    public const NAMA_SISTEM_BAWAAN   = 'DADUK';
    public const NAMA_INSTANSI_BAWAAN = 'Disdukcapil Kota Cimahi';

    /** Baris tunggal — dibuat otomatis kalau belum ada. */
    public static function current(): self
    {
        return self::query()->first() ?? self::create([]);
    }

    /** Selalu ada isinya: nilai tersimpan kalau diisi, kalau tidak teks bawaan. */
    public function getNamaSistemTampilAttribute(): string
    {
        return trim((string) $this->nama_sistem) ?: self::NAMA_SISTEM_BAWAAN;
    }

    public function getNamaInstansiTampilAttribute(): string
    {
        return trim((string) $this->nama_instansi) ?: self::NAMA_INSTANSI_BAWAAN;
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
