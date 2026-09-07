<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache query dashboard yang berat (KPI, tren, peta, rincian per kategori),
 * di-invalidasi begitu import berhasil — lihat CLAUDE.md bagian "Default
 * Behavior" & Phase 8.
 *
 * CACHE_DRIVER aplikasi ini adalah "file" (lihat .env), yang TIDAK mendukung
 * Cache::tags() (hanya redis/memcached/database yang mendukung). Karena itu
 * dipakai pola "versi" yang portable ke driver apa pun: setiap key cache
 * dashboard menyertakan angka versi ini, dan flush() cukup menaikkan angkanya
 * — tanpa perlu tahu/menghapus key satu per satu. Key lama otomatis basi
 * (tidak pernah dibaca lagi) dan hilang sendiri saat TTL habis.
 */
class DashboardCacheService
{
    private const KEY_VERSI = 'dashboard_cache_versi';

    /** TTL aman untuk data yang jarang berubah (jam, bukan menit) — invalidasi
     *  utama tetap lewat flush(), TTL ini cuma jaring pengaman kalau ada jalur
     *  penulisan data yang lupa memanggil flush(). */
    public const TTL_DETIK = 3600;

    public function versi(): int
    {
        return (int) Cache::get(self::KEY_VERSI, 1);
    }

    /** Panggil setelah data_agregat berubah (import berhasil, hapus data import, koreksi manual). */
    public function flush(): void
    {
        Cache::forever(self::KEY_VERSI, $this->versi() + 1);
    }

    /** Bangun key cache yang otomatis basi begitu flush() dipanggil. */
    public function key(string $prefix, array $bagian): string
    {
        $bagian = collect($bagian)->map(fn ($v) => $v ?? '_')->implode(':');

        return "dash:v{$this->versi()}:{$prefix}:{$bagian}";
    }
}
