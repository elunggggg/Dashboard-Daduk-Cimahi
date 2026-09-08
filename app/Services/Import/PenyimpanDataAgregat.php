<?php

namespace App\Services\Import;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\ImportExcel;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menyimpan HasilPratinjau ke data_agregat — satu-satunya kelas di alur import
 * yang boleh menulis ke database (Prinsip 6).
 *
 * Semua penulisan dibungkus satu transaction. Kalau ada apa pun yang gagal di
 * tengah, seluruhnya dibatalkan. Data agregat kependudukan tidak boleh masuk
 * separuh: angka yang tampil di dashboard publik harus utuh satu periode, bukan
 * campuran periode lama dan baru.
 */
class PenyimpanDataAgregat
{
    /** Nilai lama ditimpa nilai dari berkas. */
    public const DUPLIKAT_TIMPA = 'timpa';

    /** Nilai lama dipertahankan, baris dari berkas diabaikan. */
    public const DUPLIKAT_LEWATI = 'lewati';

    /** Batas jumlah perubahan yang dirinci di audit log. */
    private const MAKS_RINCIAN_AUDIT = 200;

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly DashboardCacheService $cache,
    ) {
    }

    /**
     * Tandai kombinasi yang sudah punya angka di database.
     *
     * Dipanggil SEBELUM halaman pratinjau dirender, dan sengaja tidak membuat
     * baris dimensi apa pun — kalau periode atau kategorinya memang belum ada,
     * berarti mustahil ada duplikat, jadi tidak ada yang perlu dibuat.
     */
    public function deteksiDuplikat(HasilPratinjau $hasil): void
    {
        $hasil->duplikat = [];

        if ($hasil->tahun === null || $hasil->semester === null) {
            return;
        }

        $waktu = DimWaktu::where('tahun', $hasil->tahun)
            ->where('semester', $hasil->semester)
            ->first();

        if (! $waktu) {
            return;
        }

        $kategori = $this->petaKategoriYangAda($hasil);

        if ($kategori === []) {
            return;
        }

        $existing = DataAgregat::query()
            ->where('waktu_id', $waktu->id)
            ->whereIn('kategori_id', array_values($kategori))
            ->pluck('jumlah', DB::raw("CONCAT(wilayah_id,'-',kategori_id)"))
            ->all();

        foreach ($this->rapikan($hasil) as $baris) {
            $kategoriId = $kategori[$this->kunciKategori($baris['jenis_indikator'], $baris['label'])] ?? null;

            if ($kategoriId === null) {
                continue;
            }

            $kunci = "{$baris['wilayah_id']}-{$kategoriId}";

            if (! array_key_exists($kunci, $existing)) {
                continue;
            }

            $hasil->duplikat[] = [
                'nama_kelurahan'  => $baris['nama_kelurahan'],
                'jenis_indikator' => $baris['jenis_indikator'],
                'label'           => $baris['label'],
                'jumlah_lama'     => (int) $existing[$kunci],
                'jumlah_baru'     => $baris['jumlah'],
            ];
        }
    }

    /**
     * Tulis seluruh baris valid.
     *
     * @return array{baru:int, diperbarui:int, dilewati:int}
     */
    public function simpan(HasilPratinjau $hasil, ImportExcel $import, string $modeDuplikat): array
    {
        if ($hasil->adaGalat()) {
            throw new RuntimeException('Import ditolak: masih ada galat struktur yang belum diselesaikan.');
        }

        if ($hasil->tahun === null || $hasil->semester === null) {
            throw new RuntimeException('Import ditolak: tahun dan semester wajib ditentukan.');
        }

        $baris = $this->rapikan($hasil);

        if ($baris === []) {
            throw new RuntimeException('Import ditolak: tidak ada satu pun baris yang bisa disimpan.');
        }

        $ringkas = DB::transaction(function () use ($baris, $hasil, $import, $modeDuplikat) {
            $userId = auth()->id();

            // Periode dibuat di sini, bukan lewat migrasi: menambah semester baru
            // cukup dengan mengimpor berkasnya (Skalabilitas, bagian 6).
            //
            // `label` wajib diisi — kolomnya NOT NULL dan dipakai apa adanya
            // sebagai teks di dropdown filter. Formatnya mengikuti DimWaktuSeeder
            // ("S2 2025") supaya periode hasil import tidak tampil beda sendiri.
            $waktu = DimWaktu::firstOrCreate(
                ['tahun' => $hasil->tahun, 'semester' => $hasil->semester],
                ['label' => "S{$hasil->semester} {$hasil->tahun}"],
            );

            $kategori = $this->petaKategoriDenganPembuatan($baris);

            $existing = DataAgregat::query()
                ->where('waktu_id', $waktu->id)
                ->whereIn('kategori_id', array_values($kategori))
                ->get(['id', 'wilayah_id', 'kategori_id', 'jumlah'])
                ->keyBy(fn ($d) => "{$d->wilayah_id}-{$d->kategori_id}");

            $baru = $diperbarui = $dilewati = 0;
            $rincian = [];

            foreach ($baris as $b) {
                $kategoriId = $kategori[$this->kunciKategori($b['jenis_indikator'], $b['label'])];
                $kunci      = "{$b['wilayah_id']}-{$kategoriId}";
                $lama       = $existing->get($kunci);

                if ($lama === null) {
                    DataAgregat::create([
                        'wilayah_id'  => $b['wilayah_id'],
                        'waktu_id'    => $waktu->id,
                        'kategori_id' => $kategoriId,
                        'jumlah'      => $b['jumlah'],
                        'import_id'   => $import->id,
                        'created_by'  => $userId,
                        'updated_by'  => $userId,
                    ]);
                    $baru++;

                    continue;
                }

                if ($modeDuplikat === self::DUPLIKAT_LEWATI) {
                    $dilewati++;

                    continue;
                }

                // Nilai yang tidak berubah tidak perlu ditulis ulang — menghemat
                // query sekaligus menjaga audit log tetap bermakna.
                if ((int) $lama->jumlah === $b['jumlah']) {
                    $dilewati++;

                    continue;
                }

                if (count($rincian) < self::MAKS_RINCIAN_AUDIT) {
                    $rincian[] = [
                        'kelurahan' => $b['nama_kelurahan'],
                        'indikator' => $b['jenis_indikator'].'/'.$b['label'],
                        'lama'      => (int) $lama->jumlah,
                        'baru'      => $b['jumlah'],
                    ];
                }

                DataAgregat::whereKey($lama->id)->update([
                    'jumlah'     => $b['jumlah'],
                    'import_id'  => $import->id,
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]);
                $diperbarui++;
            }

            $import->update([
                'status'        => ImportExcel::STATUS_BERHASIL,
                'tahun'         => $hasil->tahun,
                'semester'      => $hasil->semester,
                'jumlah_baris'  => $baru + $diperbarui,
                'pesan_error'   => null,
                'catatan_hasil' => $hasil->untukCatatan() + [
                    'baru'          => $baru,
                    'diperbarui'    => $diperbarui,
                    'dilewati'      => $dilewati,
                    'mode_duplikat' => $modeDuplikat,
                ],
            ]);

            // Satu catatan audit per import, bukan per baris. 690 entri untuk
            // satu berkas akan menenggelamkan seluruh riwayat aktivitas lain.
            $this->audit->record(
                AuditLogService::AKSI_IMPORT,
                'data_agregat',
                $rincian !== [] ? ['perubahan' => $rincian] : null,
                [
                    'import_id'     => $import->id,
                    'nama_file'     => $import->nama_file,
                    'periode'       => "Semester {$hasil->semester} Tahun {$hasil->tahun}",
                    'baru'          => $baru,
                    'diperbarui'    => $diperbarui,
                    'dilewati'      => $dilewati,
                    'mode_duplikat' => $modeDuplikat,
                    'rincian_dipotong' => $diperbarui > self::MAKS_RINCIAN_AUDIT,
                ],
            );

            return ['baru' => $baru, 'diperbarui' => $diperbarui, 'dilewati' => $dilewati];
        });

        // Di LUAR transaction — hanya jalan kalau transaksinya benar-benar commit
        // (kalau ada exception di atas, baris ini tidak pernah tercapai, cache
        // lama tetap valid). Lihat DashboardCacheService untuk skema versinya.
        $this->cache->flush();

        return $ringkas;
    }

    /**
     * Gabungkan baris duplikat di dalam berkas yang sama.
     *
     * Bisa terjadi bila dua baris konfigurasi tanpa sengaja menunjuk ke
     * (jenis_indikator, label) yang sama. Tanpa penggabungan ini, unique
     * constraint di data_agregat akan menolak transaksinya di tengah jalan;
     * dengan penggabungan, yang terbaca terakhir yang dipakai.
     *
     * @return array<string, array<string, mixed>>
     */
    private function rapikan(HasilPratinjau $hasil): array
    {
        $rapi = [];

        foreach ($hasil->baris as $b) {
            $kunci = $b['wilayah_id'].'|'.$this->kunciKategori($b['jenis_indikator'], $b['label']);
            $rapi[$kunci] = $b;
        }

        return $rapi;
    }

    /**
     * Kategori yang SUDAH ada saja — dipakai saat deteksi duplikat (read-only).
     *
     * @return array<string, int>
     */
    private function petaKategoriYangAda(HasilPratinjau $hasil): array
    {
        $peta = [];

        foreach (DimKategori::all(['id', 'jenis_indikator', 'label']) as $k) {
            $peta[$this->kunciKategori($k->jenis_indikator, $k->label)] = $k->id;
        }

        return $peta;
    }

    /**
     * Kategori untuk penyimpanan — yang belum ada dibuatkan.
     *
     * Kategori sengaja dibuat otomatis dari konfigurasi import, karena
     * konfigurasi itu sendiri sudah dikendalikan Petugas. Memaksa Petugas
     * mendaftarkan label dua kali (sekali di konfigurasi, sekali di master
     * kategori) hanya menambah langkah yang gampang terlupa.
     *
     * @param  array<string, array<string, mixed>>  $baris
     * @return array<string, int>
     */
    private function petaKategoriDenganPembuatan(array $baris): array
    {
        $peta = [];

        foreach (DimKategori::all(['id', 'jenis_indikator', 'label']) as $k) {
            $peta[$this->kunciKategori($k->jenis_indikator, $k->label)] = $k->id;
        }

        foreach ($baris as $b) {
            $kunci = $this->kunciKategori($b['jenis_indikator'], $b['label']);

            if (! isset($peta[$kunci])) {
                $peta[$kunci] = DimKategori::firstOrCreate([
                    'jenis_indikator' => $b['jenis_indikator'],
                    'label'           => $b['label'],
                ])->id;
            }
        }

        return $peta;
    }

    /** Kunci pencocokan kategori yang tahan beda huruf besar/kecil & spasi. */
    private function kunciKategori(string $jenis, string $label): string
    {
        return mb_strtolower(trim($jenis)).'::'.mb_strtolower(trim($label));
    }
}
