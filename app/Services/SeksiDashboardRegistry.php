<?php

namespace App\Services;

use App\Models\SeksiDashboard;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal status tampil/sembunyi, urutan, lebar, dan HALAMAN tiap
 * bagian halaman publik.
 *
 * `BAWAAN` = keadaan AWAL (persis tata letak sebelum fitur "Bagian Dashboard"
 * ada). Dipakai dua tempat:
 *   1. `daftar()` — bagian yang belum tercatat dibuat dari sini saat halaman
 *      publik pertama kali dirender.
 *   2. `SeksiDashboardController::reset()` — kosongkan tabel lalu isi ulang dari
 *      sini, sehingga "Reset ke Awal" benar-benar mengembalikan tata letak awal.
 *
 * Registry ini singleton (AppServiceProvider) → tabel dibaca sekali per request.
 */
class SeksiDashboardRegistry
{
    public const LEBAR_VALID = ['sepertiga', 'separuh', 'penuh'];

    /**
     * Keadaan awal seluruh bagian. HARUS sinkron dengan atribut <x-seksi> di
     * demografi/_konten, sosial/_konten, dan mobilitas/index.
     *
     * @var array<string, array{halaman:string, judul:string, urutan:int, lebar:string}>
     */
    public const BAWAAN = [
        // ── Demografi ──
        'kpi_demografi'            => ['halaman' => 'demografi', 'judul' => 'KPI Ringkas Demografi (Total / L / P / Rasio / Kepadatan)', 'urutan' => 0,   'lebar' => 'penuh'],
        'statistik_ringkas'       => ['halaman' => 'demografi', 'judul' => 'Umur Median · Laju Pertumbuhan · WNA',                     'urutan' => 10,  'lebar' => 'penuh'],
        'jenis_kelamin'           => ['halaman' => 'demografi', 'judul' => 'Jenis Kelamin',                                           'urutan' => 20,  'lebar' => 'sepertiga'],
        'anak'                    => ['halaman' => 'demografi', 'judul' => 'Jumlah Anak (0-14 Tahun)',                                 'urutan' => 30,  'lebar' => 'sepertiga'],
        'lansia'                  => ['halaman' => 'demografi', 'judul' => 'Jumlah Penduduk Lansia (65+ Tahun)',                       'urutan' => 40,  'lebar' => 'sepertiga'],
        'piramida'                => ['halaman' => 'demografi', 'judul' => 'Piramida Penduduk',                                        'urutan' => 50,  'lebar' => 'penuh'],
        'status_perkawinan'       => ['halaman' => 'demografi', 'judul' => 'Status Perkawinan',                                        'urutan' => 60,  'lebar' => 'separuh'],
        'disabilitas'             => ['halaman' => 'demografi', 'judul' => 'Disabilitas (menurut jenis keterbatasan)',                 'urutan' => 70,  'lebar' => 'separuh'],
        'kelompok_umur'           => ['halaman' => 'demografi', 'judul' => 'Distribusi Kelompok Umur',                                 'urutan' => 80,  'lebar' => 'penuh'],
        'umur_tunggal'            => ['halaman' => 'demografi', 'judul' => 'Umur Tunggal (0-99 Tahun)',                                'urutan' => 90,  'lebar' => 'penuh'],
        'kelahiran'               => ['halaman' => 'demografi', 'judul' => 'Kelahiran (CBR / GFR)',                                    'urutan' => 100, 'lebar' => 'penuh'],
        'asfr'                    => ['halaman' => 'demografi', 'judul' => 'Angka Kelahiran Menurut Kelompok Umur (ASFR & TFR)',       'urutan' => 110, 'lebar' => 'penuh'],
        'perkawinan_kecamatan'    => ['halaman' => 'demografi', 'judul' => 'Status Perkawinan per Kecamatan',                         'urutan' => 120, 'lebar' => 'penuh'],
        'disabilitas_kecamatan'   => ['halaman' => 'demografi', 'judul' => 'Disabilitas per Kecamatan',                              'urutan' => 130, 'lebar' => 'separuh'],
        'golongan_darah_kecamatan'=> ['halaman' => 'demografi', 'judul' => 'Golongan Darah per Kecamatan',                           'urutan' => 140, 'lebar' => 'separuh'],
        'disabilitas_pekerjaan'   => ['halaman' => 'demografi', 'judul' => 'Disabilitas Menurut Pekerjaan',                          'urutan' => 150, 'lebar' => 'separuh'],
        'disabilitas_usia_sekolah'=> ['halaman' => 'demografi', 'judul' => 'Disabilitas Usia Sekolah per Kecamatan',                 'urutan' => 160, 'lebar' => 'separuh'],

        // ── Sosial ──
        'dokumen_progress'        => ['halaman' => 'sosial', 'judul' => 'Kepemilikan Dokumen Kependudukan (progress)',               'urutan' => 10,  'lebar' => 'penuh'],
        'kia'                     => ['halaman' => 'sosial', 'judul' => 'Kepemilikan KIA',                                            'urutan' => 20,  'lebar' => 'separuh'],
        'akta_lahir'              => ['halaman' => 'sosial', 'judul' => 'Kepemilikan Akta Lahir',                                     'urutan' => 30,  'lebar' => 'separuh'],
        'pendidikan'              => ['halaman' => 'sosial', 'judul' => 'Pendidikan',                                                 'urutan' => 40,  'lebar' => 'separuh'],
        'pekerjaan'               => ['halaman' => 'sosial', 'judul' => 'Pekerjaan',                                                  'urutan' => 50,  'lebar' => 'separuh'],
        'ktp'                     => ['halaman' => 'sosial', 'judul' => 'Kepemilikan KTP',                                            'urutan' => 60,  'lebar' => 'sepertiga'],
        'kk'                      => ['halaman' => 'sosial', 'judul' => 'Kepemilikan KK',                                             'urutan' => 70,  'lebar' => 'sepertiga'],
        'golongan_darah'          => ['halaman' => 'sosial', 'judul' => 'Golongan Darah',                                            'urutan' => 80,  'lebar' => 'sepertiga'],
        'jenis_pekerjaan'         => ['halaman' => 'sosial', 'judul' => 'Rincian Jenis Pekerjaan',                                    'urutan' => 90,  'lebar' => 'separuh'],
        'usia_sekolah'            => ['halaman' => 'sosial', 'judul' => 'Penduduk Usia Sekolah',                                      'urutan' => 100, 'lebar' => 'separuh'],
        'agama'                   => ['halaman' => 'sosial', 'judul' => 'Agama',                                                      'urutan' => 110, 'lebar' => 'penuh'],
        'kepala_keluarga'         => ['halaman' => 'sosial', 'judul' => 'Kepala Keluarga (menurut jenis kelamin)',                   'urutan' => 120, 'lebar' => 'penuh'],
        'angkatan_kerja'          => ['halaman' => 'sosial', 'judul' => 'Angkatan Kerja & TPAK',                                      'urutan' => 130, 'lebar' => 'penuh'],
        'kk_rincian'              => ['halaman' => 'sosial', 'judul' => 'Kepala Keluarga: Rincian Demografi',                         'urutan' => 140, 'lebar' => 'penuh'],
        'kk_kawin_kecamatan'      => ['halaman' => 'sosial', 'judul' => 'Status Perkawinan KK per Kecamatan',                        'urutan' => 150, 'lebar' => 'separuh'],
        'agama_kecamatan'         => ['halaman' => 'sosial', 'judul' => 'Agama per Kecamatan',                                        'urutan' => 160, 'lebar' => 'separuh'],
        'ak_pendidikan'           => ['halaman' => 'sosial', 'judul' => 'Angkatan Kerja Menurut Tingkat Pendidikan',                 'urutan' => 170, 'lebar' => 'penuh'],
        'penerbitan_dokumen'      => ['halaman' => 'sosial', 'judul' => 'Penerbitan Dokumen Se-Kota',                                'urutan' => 180, 'lebar' => 'penuh'],
        'shbkel'                  => ['halaman' => 'sosial', 'judul' => 'Status Hubungan dalam Keluarga',                             'urutan' => 190, 'lebar' => 'penuh'],
        'akta_lahir_kelurahan'    => ['halaman' => 'sosial', 'judul' => 'Wajib Akta Lahir per Kelurahan',                            'urutan' => 200, 'lebar' => 'sepertiga'],
        'kia_kelurahan'           => ['halaman' => 'sosial', 'judul' => 'Wajib KIA per Kelurahan',                                    'urutan' => 210, 'lebar' => 'sepertiga'],
        'ktp_kelurahan'           => ['halaman' => 'sosial', 'judul' => 'Wajib KTP per Kelurahan',                                    'urutan' => 220, 'lebar' => 'sepertiga'],

        // ── Mobilitas (lebar tidak dipakai — halaman itu bukan grid cair) ──
        'pendatang_kelurahan'     => ['halaman' => 'mobilitas', 'judul' => 'Pendatang per Kelurahan',                                 'urutan' => 10,  'lebar' => 'penuh'],
        'pindah_kelurahan'        => ['halaman' => 'mobilitas', 'judul' => 'Pindah Keluar per Kelurahan',                             'urutan' => 20,  'lebar' => 'penuh'],
        'tren_antar_periode'      => ['halaman' => 'mobilitas', 'judul' => 'Tren Mobilitas Antar Periode',                            'urutan' => 30,  'lebar' => 'penuh'],
    ];

    /** @var array<string, \App\Models\SeksiDashboard> ditandai "kunci" (unik lintas halaman) */
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
     * Dipanggil komponen <x-seksi>. Mengembalikan baris SeksiDashboard untuk
     * `kunci` — dibuat sekali kalau belum ada, memakai BAWAAN sebagai default
     * (bila kunci tak dikenal, pakai argumen dari Blade sebagai cadangan).
     */
    public function daftar(string $kunci, string $halamanCadangan, string $judulCadangan, int $urutanCadangan = 0, string $lebarCadangan = 'sepertiga'): SeksiDashboard
    {
        $this->muat();

        $awal = self::BAWAAN[$kunci] ?? [
            'halaman' => $halamanCadangan,
            'judul'   => $judulCadangan,
            'urutan'  => $urutanCadangan,
            'lebar'   => in_array($lebarCadangan, self::LEBAR_VALID, true) ? $lebarCadangan : 'sepertiga',
        ];

        if (! isset($this->map[$kunci])) {
            $this->map[$kunci] = SeksiDashboard::create([
                'halaman' => $awal['halaman'],
                'kunci'   => $kunci,
                'judul'   => $awal['judul'],
                'tampil'  => true,
                'lebar'   => $awal['lebar'],
                'urutan'  => $awal['urutan'],
            ]);
        } elseif ($this->map[$kunci]->judul !== $awal['judul']) {
            // Judul berubah di kode — samakan tanpa menyentuh pengaturan Petugas lain.
            $this->map[$kunci]->forceFill(['judul' => $awal['judul']])->save();
        }

        return $this->map[$kunci];
    }

    /** Isi tabel dari BAWAAN (dipakai "Reset ke Awal"). */
    public function seedBawaan(): void
    {
        $now = now();
        SeksiDashboard::insert(collect(self::BAWAAN)->map(fn ($v, $kunci) => [
            'halaman'    => $v['halaman'],
            'kunci'      => $kunci,
            'judul'      => $v['judul'],
            'tampil'     => true,
            'lebar'      => $v['lebar'],
            'urutan'     => $v['urutan'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
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
