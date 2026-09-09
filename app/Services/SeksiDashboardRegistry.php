<?php

namespace App\Services;

use App\Models\SeksiDashboard;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal status tampil/sembunyi, urutan, lebar, dan HALAMAN tiap
 * bagian halaman publik.
 *
 * Didaftarkan singleton (AppServiceProvider) supaya seluruh tabel
 * `seksi_dashboard` cukup dibaca SEKALI per request. Bagian yang belum pernah
 * tercatat otomatis dibuat saat pertama kali dirender (default: tampil, lebar
 * bawaan sesuai deklarasi di Blade, halaman = tempat ia dideklarasikan) — jadi
 * daftar di halaman "Bagian Dashboard" selalu mengikuti Blade tanpa seeder.
 */
class SeksiDashboardRegistry
{
    public const LEBAR_VALID = ['sepertiga', 'separuh', 'penuh'];

    /** @var array<string, \App\Models\SeksiDashboard> ditandai "kunci" (kunci unik lintas halaman) */
    private array $map;

    private bool $dimuat = false;

    /** Halaman publik yang sedang dirender — dipakai <x-seksi> untuk memutuskan render/tidak. */
    private ?string $halamanAktif = null;

    public function setHalamanAktif(?string $halaman): void
    {
        $this->halamanAktif = $halaman;
    }

    public function halamanAktif(): ?string
    {
        return $this->halamanAktif;
    }

    private function muat(): void
    {
        if ($this->dimuat) {
            return;
        }

        $this->map = SeksiDashboard::all()->keyBy('kunci')->all();
        $this->dimuat = true;
    }

    /**
     * Dipanggil komponen <x-seksi> DAN grid loop. Mengembalikan baris
     * SeksiDashboard untuk `kunci` — dibuat sekali kalau belum ada.
     *
     * @param  string  $halamanAsli  halaman tempat bagian ini dideklarasikan di Blade (jadi default `halaman`)
     */
    public function daftar(string $kunci, string $halamanAsli, string $judul, int $urutan = 0, string $lebar = 'sepertiga'): SeksiDashboard
    {
        $this->muat();

        if (! isset($this->map[$kunci])) {
            $this->map[$kunci] = SeksiDashboard::create([
                'halaman' => $halamanAsli,
                'kunci'   => $kunci,
                'judul'   => $judul,
                'tampil'  => true,
                'lebar'   => in_array($lebar, self::LEBAR_VALID, true) ? $lebar : 'sepertiga',
                'urutan'  => $urutan,
            ]);
        } elseif ($this->map[$kunci]->judul !== $judul) {
            // Judul di Blade berubah — samakan diam-diam (tanpa menyentuh halaman/urutan/lebar
            // yang mungkin sudah diatur Petugas).
            $this->map[$kunci]->forceFill(['judul' => $judul])->save();
        }

        return $this->map[$kunci];
    }

    /** Bagian yang HARUS dirender pada $halaman ini, sudah terurut. */
    public function untukHalaman(string $halaman): Collection
    {
        $this->muat();

        return collect($this->map)
            ->filter(fn (SeksiDashboard $s) => $s->halaman === $halaman && $s->tampil)
            ->sortBy([['urutan', 'asc'], ['id', 'asc']])
            ->values();
    }

    /** Semua bagian, dikelompokkan per halaman & diurutkan — untuk UI kelola. */
    public function semua(): Collection
    {
        return SeksiDashboard::orderBy('halaman')->orderBy('urutan')->orderBy('id')->get()
            ->groupBy('halaman');
    }
}
