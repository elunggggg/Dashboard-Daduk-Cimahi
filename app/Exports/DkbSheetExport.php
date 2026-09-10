<?php

namespace App\Exports;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWilayah;
use App\Models\KonfigurasiImport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Satu sheet ekspor = satu jenis_indikator, ditata meniru berkas DKB
 * Disdukcapil: baris = wilayah (15 kelurahan + subtotal kecamatan + total
 * Kota, atau 3 kecamatan, atau 1 baris Kota — mengikuti granularitas data),
 * kolom = kategori indikator. Indikator yang punya rincian jenis kelamin
 * ({jenis}_l/_p) memakai sub-kolom Laki-laki | Perempuan | Jumlah per kategori.
 *
 * Bila lebih dari satu periode diekspor, tiap periode jadi BLOK tabel sendiri
 * yang ditumpuk vertikal (judul periode di atas tiap blok).
 *
 * Indikator berupa RASIO / RATA-RATA (kepadatan, LPP, umur median, rasio-…)
 * tidak dijumlahkan: barisnya hanya per wilayah, tanpa subtotal/total dan
 * tanpa kolom "Jumlah Seluruhnya" (menjumlahkan rasio tidak bermakna).
 */
class DkbSheetExport implements FromArray, WithTitle, WithEvents, ShouldAutoSize
{
    /** jenis_indikator yang nilainya rasio/rata-rata → tidak dijumlahkan. */
    private const NON_AGREGATIF = [
        'kepadatan_penduduk', 'lpp', 'umur_median',
        'rasio_jenis_kelamin', 'rasio_ketergantungan', 'rasio_pindah_datang',
    ];

    /** @var array<int, array<int, mixed>> */
    private array $grid = [];

    /** @var array<int, string> rentang sel yang di-merge, mis. "A1:F1" */
    private array $merges = [];

    /** @var array<int, int> nomor baris (1-based) subtotal/total → tebal + arsir */
    private array $barisRekap = [];

    /** @var array<int, int> nomor baris (1-based) judul + header kolom → putih di navy */
    private array $barisHeader = [];

    private int $kolomTerakhir = 2;

    private bool $lp;

    private bool $agregatif;

    public function __construct(
        private readonly string $jenis,
        private readonly string $judul,
        private readonly Collection $periode,
        private readonly ?string $kecamatan,
        private readonly string $namaSheet,
    ) {
        $this->lp        = DataAgregatExport::punyaRincianJk($this->jenis);
        $this->agregatif = ! in_array($this->jenis, self::NON_AGREGATIF, true);
        $this->bangun();
    }

    public function title(): string
    {
        return $this->namaSheet;
    }

    public function array(): array
    {
        return $this->grid;
    }

    // ── Penyusunan grid ─────────────────────────────────────────────────────

    private function bangun(): void
    {
        $kategori = $this->kategoriTerurut();
        $subKolom = $this->lp ? 3 : 1;

        // No + Wilayah + (kategori × subKolom) [+ Jumlah Seluruhnya × subKolom]
        $this->kolomTerakhir = 2 + count($kategori) * $subKolom + ($this->agregatif ? $subKolom : 0);

        $periode = $this->periode->sortBy('tahun')->sortBy('semester')->values();

        foreach ($periode as $idx => $waktu) {
            $this->blokPeriode($waktu, $kategori, $subKolom);

            if ($idx < $periode->count() - 1) {
                $this->push([]); // baris kosong pemisah antar-blok
            }
        }
    }

    /**
     * Tambah satu baris ke grid, dipadatkan ke lebar tetap (kolomTerakhir)
     * dengan string kosong — supaya indeks grid = nomor baris Excel PERSIS
     * (baris `[]` diabaikan PhpSpreadsheet dan akan menggeser semua nomor).
     */
    private function push(array $row): int
    {
        $row = array_slice($row, 0, $this->kolomTerakhir);
        $row = array_pad($row, $this->kolomTerakhir, '');
        $this->grid[] = array_values($row);

        return count($this->grid); // 1-based
    }

    /** @param  array<int, string>  $kategori */
    private function blokPeriode($waktu, array $kategori, int $subKolom): void
    {
        [$nilai, $granularitas] = $this->kumpulkanNilai($waktu->id);

        // ── Judul blok ──
        $rJudul = $this->push([mb_strtoupper($this->judul).' — '.$waktu->label]);
        $this->merges[]      = 'A'.$rJudul.':'.$this->huruf($this->kolomTerakhir).$rJudul;
        $this->barisHeader[] = $rJudul;

        // ── Header kolom (baris 1) ──
        $judulKolom = $kategori;
        if ($this->agregatif) {
            $judulKolom[] = 'Jumlah Seluruhnya';
        }

        $rH1 = count($this->grid) + 1; // baris tempat h1 akan ditulis
        $h1  = ['No', 'Wilayah'];
        $c   = 3;
        foreach ($judulKolom as $label) {
            $h1[] = $label;
            for ($i = 1; $i < $subKolom; $i++) {
                $h1[] = '';
            }
            if ($subKolom > 1) {
                $this->merges[] = $this->huruf($c).$rH1.':'.$this->huruf($c + $subKolom - 1).$rH1;
            }
            $c += $subKolom;
        }
        $this->push($h1);
        $this->barisHeader[] = $rH1;

        // ── Header kolom (baris 2: L/P/Jumlah) — hanya bila LP ──
        if ($this->lp) {
            $h2 = ['', ''];
            foreach ($judulKolom as $ignored) {
                array_push($h2, 'Laki-laki', 'Perempuan', 'Jumlah');
            }
            $rH2 = $this->push($h2);
            $this->barisHeader[] = $rH2;
            $this->merges[] = 'A'.$rH1.':A'.$rH2;
            $this->merges[] = 'B'.$rH1.':B'.$rH2;
        }

        // ── Baris data ──
        $no = 0;
        foreach ($this->daftarBarisWilayah($granularitas) as $item) {
            if ($item['tipe'] === 'kelurahan') {
                $no++;
                $this->push($this->barisNilai((string) $no, mb_strtoupper($item['nama']), $item['ids'], $nilai, $kategori));
            } else {
                $this->barisRekap[] = $this->push(
                    $this->barisNilai('', $item['nama'], $item['ids'], $nilai, $kategori)
                );
            }
        }
    }

    /**
     * @param  array<int, int>  $wilayahIds
     * @param  array<int, array<string, array<string, int>>>  $nilai
     * @param  array<int, string>  $kategori
     * @return array<int, mixed>
     */
    private function barisNilai(string $no, string $nama, array $wilayahIds, array $nilai, array $kategori): array
    {
        $row = [$no, $nama];
        $tot = ['laki' => 0, 'perempuan' => 0, 'total' => 0];

        foreach ($kategori as $label) {
            $sel = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
            foreach ($wilayahIds as $wid) {
                $sel['laki']      += $nilai[$wid][$label]['laki'] ?? 0;
                $sel['perempuan'] += $nilai[$wid][$label]['perempuan'] ?? 0;
                $sel['total']     += $nilai[$wid][$label]['total'] ?? 0;
            }

            if ($this->lp) {
                array_push($row, $sel['laki'], $sel['perempuan'], $sel['total']);
            } else {
                $row[] = $sel['total'];
            }

            $tot['laki']      += $sel['laki'];
            $tot['perempuan'] += $sel['perempuan'];
            $tot['total']     += $sel['total'];
        }

        if ($this->agregatif) {
            if ($this->lp) {
                array_push($row, $tot['laki'], $tot['perempuan'], $tot['total']);
            } else {
                $row[] = $tot['total'];
            }
        }

        return $row;
    }

    // ── Data & struktur wilayah ─────────────────────────────────────────────

    /**
     * @return array{0: array<int, array<string, array<string, int>>>, 1: string}
     */
    private function kumpulkanNilai(int $waktuId): array
    {
        $jenisSet = $this->lp ? [$this->jenis, $this->jenis.'_l', $this->jenis.'_p'] : [$this->jenis];

        $rows = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->whereIn('jenis_indikator', $jenisSet))
            ->where('waktu_id', $waktuId)
            ->when($this->kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($w) => $w->where('nama_kecamatan', $this->kecamatan)))
            ->with(['wilayah:id,is_kota,is_kecamatan', 'kategori:id,jenis_indikator,label'])
            ->get();

        $nilai = [];
        $adaKelurahan = false;
        $adaKecamatan = false;

        foreach ($rows as $r) {
            $j   = $r->kategori->jenis_indikator;
            $sub = str_ends_with($j, '_l') ? 'laki' : (str_ends_with($j, '_p') ? 'perempuan' : 'total');
            $nilai[$r->wilayah_id][$r->kategori->label][$sub] = (int) $r->jumlah;

            if ($r->wilayah && ! $r->wilayah->is_kota && ! $r->wilayah->is_kecamatan) {
                $adaKelurahan = true;
            } elseif ($r->wilayah && $r->wilayah->is_kecamatan) {
                $adaKecamatan = true;
            }
        }

        return [$nilai, $adaKelurahan ? 'kelurahan' : ($adaKecamatan ? 'kecamatan' : 'kota')];
    }

    /** @return array<int, array{tipe: string, nama: string, ids: array<int, int>}> */
    private function daftarBarisWilayah(string $granularitas): array
    {
        $out = [];

        if ($granularitas === 'kelurahan') {
            $kelurahan = DimWilayah::kelurahan()
                ->when($this->kecamatan, fn ($q) => $q->where('nama_kecamatan', $this->kecamatan))
                ->orderBy('kode_kemendagri')
                ->get(['id', 'nama_kelurahan', 'nama_kecamatan']);

            $semua = [];
            foreach ($kelurahan->groupBy('nama_kecamatan') as $namaKec => $grup) {
                $ids = [];
                foreach ($grup as $kel) {
                    $out[] = ['tipe' => 'kelurahan', 'nama' => $kel->nama_kelurahan, 'ids' => [$kel->id]];
                    $ids[] = $kel->id;
                }
                if ($this->agregatif) {
                    $out[] = ['tipe' => 'subtotal', 'nama' => $namaKec, 'ids' => $ids];
                }
                $semua = array_merge($semua, $ids);
            }

            if ($this->agregatif && ! $this->kecamatan) {
                $out[] = ['tipe' => 'total', 'nama' => 'KOTA CIMAHI', 'ids' => $semua];
            }

            return $out;
        }

        if ($granularitas === 'kecamatan') {
            $kec = DimWilayah::query()->where('is_kecamatan', true)
                ->when($this->kecamatan, fn ($q) => $q->where('nama_kecamatan', $this->kecamatan))
                ->orderBy('kode_kemendagri')
                ->get(['id', 'nama_kecamatan']);

            $ids = [];
            foreach ($kec as $k) {
                $out[] = ['tipe' => 'kelurahan', 'nama' => $k->nama_kecamatan, 'ids' => [$k->id]];
                $ids[] = $k->id;
            }
            if ($this->agregatif && ! $this->kecamatan && $kec->count() > 1) {
                $out[] = ['tipe' => 'total', 'nama' => 'KOTA CIMAHI', 'ids' => $ids];
            }

            return $out;
        }

        $kota = DimWilayah::query()->where('is_kota', true)->first(['id']);
        if ($kota) {
            $out[] = ['tipe' => 'total', 'nama' => 'KOTA CIMAHI', 'ids' => [$kota->id]];
        }

        return $out;
    }

    /**
     * Urutan kategori (kolom). Bila SEMUA label memuat angka (umur tunggal,
     * kelompok umur) → urut menurut angkanya. Selain itu → menurut posisi
     * kolom di berkas DKB (urutan baris konfigurasi_import), lalu abjad.
     *
     * @return array<int, string>
     */
    private function kategoriTerurut(): array
    {
        $label = DimKategori::query()
            ->where('jenis_indikator', $this->jenis)
            ->pluck('label')->unique()->values();

        if ($label->isEmpty()) {
            return [];
        }

        // "Deret angka": tiap label DIAWALI angka (opsional "Umur "), mis.
        // "0-4 Tahun", "Umur 17 Tahun", "75+ Tahun". Bukan sekadar memuat digit
        // (supaya "Kepadatan (Jiwa/km2)" tidak salah dianggap deret angka).
        if ($label->every(fn ($l) => preg_match('/^\s*(umur\s+)?\d/i', (string) $l))) {
            return $label
                ->sortBy(fn ($l) => (int) (preg_match('/\d+/', (string) $l, $m) ? $m[0] : 0))
                ->values()->all();
        }

        $posisi = KonfigurasiImport::query()
            ->where('jenis_indikator', $this->jenis)
            ->orderBy('id')->pluck('label')
            ->values()->flip()->all();

        return $label
            ->sortBy(fn ($l) => [$posisi[$l] ?? 9999, mb_strtolower((string) $l)])
            ->values()->all();
    }

    // ── Styling ────────────────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = $this->huruf($this->kolomTerakhir);
                $lastRow = max(count($this->grid), 1);

                foreach ($this->merges as $rentang) {
                    $sheet->mergeCells($rentang);
                }

                foreach ($this->barisHeader as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true,
                        ],
                    ]);
                }

                foreach ($this->barisRekap as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    ]);
                }

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('D1D5DB');

                $sheet->getStyle('C1:'.$lastCol.$lastRow)
                    ->getNumberFormat()->setFormatCode('#,##0');

                $sheet->getStyle('B1:B'.$lastRow)->getAlignment()->setWrapText(true);
            },
        ];
    }

    private function huruf(int $indeks1): string
    {
        return Coordinate::stringFromColumnIndex($indeks1);
    }
}
