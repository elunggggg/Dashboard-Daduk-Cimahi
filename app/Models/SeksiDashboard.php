<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu "bagian" grafik/tabel di halaman publik (Demografi/Sosial/Mobilitas)
 * yang bisa ditampilkan / disembunyikan Petugas. Lihat SeksiDashboardRegistry
 * dan komponen <x-seksi>.
 */
class SeksiDashboard extends Model
{
    protected $table = 'seksi_dashboard';

    protected $fillable = ['halaman', 'kunci', 'judul', 'tampil', 'urutan'];

    protected $casts = ['tampil' => 'boolean'];

    /** Label halaman untuk UI. */
    public const LABEL_HALAMAN = [
        'dashboard'  => 'Dashboard Publik',
        'demografi'  => 'Demografi',
        'sosial'     => 'Sosial',
        'mobilitas'  => 'Mobilitas',
    ];
}
