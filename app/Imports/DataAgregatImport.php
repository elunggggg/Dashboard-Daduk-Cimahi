<?php

namespace App\Imports;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import data agregat dari Excel/CSV.
 *
 * Kolom wajib (baris pertama = header):
 *   kode_kemendagri | tahun | semester | jenis_indikator | label | jumlah
 *
 * Bersifat all-or-nothing: bila ada satu baris tidak valid, tidak ada yang
 * disimpan. Ini disengaja — data agregat kependudukan tidak boleh masuk
 * separuh-separuh karena angkanya langsung tampil di dashboard publik.
 */
class DataAgregatImport implements ToCollection, WithHeadingRow
{
    public const KOLOM_WAJIB = [
        'kode_kemendagri', 'tahun', 'semester', 'jenis_indikator', 'label', 'jumlah',
    ];

    /** @var array<int, string> */
    public array $errors = [];

    public int $jumlahBaris = 0;

    public int $jumlahBaru = 0;

    public int $jumlahDiperbarui = 0;

    /** @var array<string, int> */
    private array $wilayah = [];

    /** @var array<string, int> */
    private array $waktu = [];

    /** @var array<string, int> */
    private array $kategori = [];

    /** Baris siap simpan, dikumpulkan dulu agar validasi tuntas sebelum menulis. */
    private array $siap = [];

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->errors[] = 'File tidak berisi baris data apa pun.';

            return;
        }

        $headerAda = array_keys($rows->first()->toArray());
        $kurang    = array_diff(self::KOLOM_WAJIB, $headerAda);
        if ($kurang) {
            $this->errors[] = 'Kolom wajib tidak ditemukan: '.implode(', ', $kurang).
                '. Unduh template untuk format yang benar.';

            return;
        }

        $this->muatReferensi();

        foreach ($rows as $i => $row) {
            // +2 : baris 1 dipakai header, dan index collection mulai dari 0
            $this->periksaBaris($row->toArray(), $i + 2);
        }
    }

    private function muatReferensi(): void
    {
        $this->wilayah = DimWilayah::pluck('id', 'kode_kemendagri')->all();

        DimWaktu::all()->each(function (DimWaktu $w) {
            $this->waktu["{$w->tahun}-{$w->semester}"] = $w->id;
        });

        DimKategori::all()->each(function (DimKategori $k) {
            // Dinormalkan agar beda huruf besar/kecil dan spasi tidak bikin gagal
            $this->kategori[$this->kunciKategori($k->jenis_indikator, $k->label)] = $k->id;
        });
    }

    private function kunciKategori(string $jenis, string $label): string
    {
        return mb_strtolower(trim($jenis)).'::'.mb_strtolower(trim($label));
    }

    private function periksaBaris(array $row, int $nomorBaris): void
    {
        // Lewati baris yang sepenuhnya kosong (umum terjadi di ekor file Excel)
        if (collect($row)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty()) {
            return;
        }

        $this->jumlahBaris++;

        $kode   = trim((string) ($row['kode_kemendagri'] ?? ''));
        $tahun  = trim((string) ($row['tahun'] ?? ''));
        $sem    = trim((string) ($row['semester'] ?? ''));
        $jenis  = trim((string) ($row['jenis_indikator'] ?? ''));
        $label  = trim((string) ($row['label'] ?? ''));
        $jumlah = trim((string) ($row['jumlah'] ?? ''));

        $wilayahId = $this->wilayah[$kode] ?? null;
        if (! $wilayahId) {
            $this->errors[] = "Baris {$nomorBaris}: kode wilayah '{$kode}' tidak terdaftar.";

            return;
        }

        $waktuId = $this->waktu["{$tahun}-{$sem}"] ?? null;
        if (! $waktuId) {
            $this->errors[] = "Baris {$nomorBaris}: periode tahun '{$tahun}' semester '{$sem}' tidak terdaftar.";

            return;
        }

        $kategoriId = $this->kategori[$this->kunciKategori($jenis, $label)] ?? null;
        if (! $kategoriId) {
            $this->errors[] = "Baris {$nomorBaris}: kombinasi indikator '{$jenis}' / kategori '{$label}' tidak terdaftar.";

            return;
        }

        if ($jumlah === '' || ! is_numeric($jumlah) || (float) $jumlah != (int) $jumlah || (int) $jumlah < 0) {
            $this->errors[] = "Baris {$nomorBaris}: jumlah '{$jumlah}' harus bilangan bulat >= 0.";

            return;
        }

        $kunci = "{$wilayahId}-{$waktuId}-{$kategoriId}";
        if (isset($this->siap[$kunci])) {
            $this->errors[] = "Baris {$nomorBaris}: duplikat wilayah+periode+kategori di dalam file yang sama.";

            return;
        }

        $this->siap[$kunci] = [
            'wilayah_id'  => $wilayahId,
            'waktu_id'    => $waktuId,
            'kategori_id' => $kategoriId,
            'jumlah'      => (int) $jumlah,
        ];
    }

    public function gagal(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Menulis seluruh baris valid. Dipanggil controller di dalam transaksi.
     */
    /**
     * @param  int|null  $importId  penanda asal-usul baris, supaya datanya bisa
     *                              dihapus lagi lewat menu Riwayat Import bila
     *                              ternyata Petugas mengunggah berkas yang salah
     */
    public function simpan(?int $importId = null, ?int $userId = null): void
    {
        foreach (array_chunk($this->siap, 500) as $chunk) {
            foreach ($chunk as $baris) {
                $adaSebelumnya = DataAgregat::where('wilayah_id', $baris['wilayah_id'])
                    ->where('waktu_id', $baris['waktu_id'])
                    ->where('kategori_id', $baris['kategori_id'])
                    ->exists();

                DataAgregat::updateOrCreate(
                    [
                        'wilayah_id'  => $baris['wilayah_id'],
                        'waktu_id'    => $baris['waktu_id'],
                        'kategori_id' => $baris['kategori_id'],
                    ],
                    [
                        'jumlah'     => $baris['jumlah'],
                        'import_id'  => $importId,
                        'updated_by' => $userId,
                    ] + ($adaSebelumnya ? [] : ['created_by' => $userId]),
                );

                $adaSebelumnya ? $this->jumlahDiperbarui++ : $this->jumlahBaru++;
            }
        }
    }
}
