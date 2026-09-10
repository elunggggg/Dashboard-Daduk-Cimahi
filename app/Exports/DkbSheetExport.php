<?php

namespace App\Exports;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWilayah;
use App\Models\KonfigurasiExport;
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
 * Disdukcapil: baris = wilayah (kelurahan + subtotal kecamatan + total Kota,
 * atau 3 kecamatan, atau 1 baris Kota — mengikuti granularitas data), kolom =
 * kategori indikator; indikator ber-rincian jenis kelamin memakai sub-kolom
 * Laki-laki | Perempuan | Jumlah per kategori.
 *
 * ELEMEN (No, judul kolom Wilayah, sub-kolom L/P/Jumlah, kolom "Jumlah
 * Seluruhnya", baris subtotal kecamatan, baris total Kota) diambil dari
 * "Konfigurasi Unduh" (tabel konfigurasi_export) — bisa dinonaktifkan &
 * di-relabel Petugas.
 *
 * Beberapa periode → tiap periode jadi BLOK tabel sendiri, ditumpuk vertikal.
 *
 * Indikator RASIO / RATA-RATA (kepadatan, LPP, umur median, rasio-…) tidak
 * dijumlahkan: baris per wilayah saja, tanpa subtotal/total & tanpa "Jumlah
 * Seluruhnya" (menjumlahkan rasio tidak bermakna).
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

    /** @var array<int, string> rentang sel yang di-merge */
    private array $merges = [];

    /** @var array<int, int> nomor baris subtotal/total → tebal + arsir */
    private array $barisRekap = [];

    /** @var array<int, int> nomor baris judul + header kolom → putih di navy */
    private array $barisHeader = [];

    private int $kolomTerakhir = 2;

    private bool $lp;            // benar-benar tampil L/P (punya rincian JK DAN elemen L/P aktif)

    // Elemen dari Konfigurasi Unduh
    private bool $adaNo;
    private bool $adaLaki;
    private bool $adaPerempuan;
    private bool $adaJumlahSeluruhnya;
    private bool $adaSubtotal;
    private bool $adaTotalKota;
    private string $labelNo;
    private string $labelWilayah;
    private string $labelLaki;
    private string $labelPerempuan;
    private string $labelJumlah;
    private string $labelJumlahSeluruhnya;
    private string $labelTotalKota;

    private int $kolIdentitas;  // 2 (No + Wilayah) atau 1 (Wilayah saja)
    private int $subKolom;      // sub-kolom per kategori

    public function __construct(
        private readonly string $jenis,
        private readonly string $judul,
        private readonly Collection $periode,
        private readonly ?string $kecamatan,
        private readonly string $namaSheet,
    ) {
        $agregatif = ! in_array($this->jenis, self::NON_AGREGATIF, true);

        $this->adaNo        = KonfigurasiExport::elemenAktif('no');
        $this->adaLaki      = KonfigurasiExport::elemenAktif('laki');
        $this->adaPerempuan = KonfigurasiExport::elemenAktif('perempuan');
        $this->adaJumlahSeluruhnya = $agregatif && KonfigurasiExport::elemenAktif('jumlah_seluruhnya');
        $this->adaSubtotal  = $agregatif && KonfigurasiExport::elemenAktif('subtotal_kecamatan');
        $this->adaTotalKota = $agregatif && KonfigurasiExport::elemenAktif('total_kota');

        $this->labelNo               = KonfigurasiExport::labelElemen('no');
        $this->labelWilayah          = KonfigurasiExport::labelElemen('wilayah');
        $this->labelLaki             = KonfigurasiExport::labelElemen('laki');
        $this->labelPerempuan        = KonfigurasiExport::labelElemen('perempuan');
        $this->labelJumlah           = KonfigurasiExport::labelElemen('jumlah');
        $this->labelJumlahSeluruhnya = KonfigurasiExport::labelElemen('jumlah_seluruhnya');
        $this->labelTotalKota        = KonfigurasiExport::labelElemen('total_kota');

        $this->lp = DataAgregatExport::punyaRincianJk($this->jenis)
            && ($this->adaLaki || $this->adaPerempuan);

        $this->kolIdentitas = $this->adaNo ? 2 : 1;
        $this->subKolom = $this->lp
            ? (int) $this->adaLaki + (int) $this->adaPerempuan + 1
            : 1;

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

        $this->kolomTerakhir = $this->kolIdentitas
            + count($kategori) * $this->subKolom
            + ($this->adaJumlahSeluruhnya ? $this->subKolom : 0);

        $periode = $this->periode->sortBy('tahun')->sortBy('semester')->values();

        foreach ($periode as $idx => $waktu) {
            $this->blokPeriode($waktu, $kategori);

            if ($idx < $periode->count() - 1) {
                $this->push([]);
            }
        }
    }

    /** Padatkan tiap baris ke lebar tetap → indeks grid = nomor baris Excel persis. */
    private function push(array $row): int
    {
        $row = array_slice($row, 0, $this->kolomTerakhir);
        $row = array_pad($row, $this->kolomTerakhir, '');
        $this->grid[] = array_values($row);

        return count($this->grid);
    }

    /** Judul-judul identitas ("No", "Wilayah") sesuai elemen aktif. */
    private function selIdentitas(string $isiWilayah = ''): array
    {
        return $this->adaNo ? ['', $isiWilayah] : [$isiWilayah];
    }

    /** @param  array<int, string>  $kategori */
    private function blokPeriode($waktu, array $kategori): void
    {
        [$nilai, $granularitas] = $this->kumpulkanNilai($waktu->id);

        // ── Judul blok ──
        $rJudul = $this->push([mb_strtoupper($this->judul).' — '.$waktu->label]);
        $this->merges[]      = 'A'.$rJudul.':'.$this->huruf($this->kolomTerakhir).$rJudul;
        $this->barisHeader[] = $rJudul;

        // ── Header kolom (baris 1) ──
        $judulKolom = $kategori;
        if ($this->adaJumlahSeluruhnya) {
            $judulKolom[] = $this->labelJumlahSeluruhnya;
        }

        $rH1 = count($this->grid) + 1;
        $h1  = $this->adaNo ? [$this->labelNo, $this->labelWilayah] : [$this->labelWilayah];
        $c   = $this->kolIdentitas + 1;
        foreach ($judulKolom as $label) {
            $h1[] = $label;
            for ($i = 1; $i < $this->subKolom; $i++) {
                $h1[] = '';
            }
            if ($this->subKolom > 1) {
                $this->merges[] = $this->huruf($c).$rH1.':'.$this->huruf($c + $this->subKolom - 1).$rH1;
            }
            $c += $this->subKolom;
        }
        $this->push($h1);
        $this->barisHeader[] = $rH1;

        // ── Header kolom (baris 2: sub-kolom L/P/Jumlah) — hanya bila LP ──
        if ($this->lp) {
            $sub = [];
            if ($this->adaLaki) {
                $sub[] = $this->labelLaki;
            }
            if ($this->adaPerempuan) {
                $sub[] = $this->labelPerempuan;
            }
            $sub[] = $this->labelJumlah;

            $h2 = $this->adaNo ? ['', ''] : [''];
            foreach ($judulKolom as $ignored) {
                array_push($h2, ...$sub);
            }
            $rH2 = $this->push($h2);
            $this->barisHeader[] = $rH2;

            $this->merges[] = 'A'.$rH1.':A'.$rH2;
            if ($this->adaNo) {
                $this->merges[] = 'B'.$rH1.':B'.$rH2;
            }
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
        $row = $this->adaNo ? [$no, $nama] : [$nama];
        $tot = ['laki' => 0, 'perempuan' => 0, 'total' => 0];

        foreach ($kategori as $label) {
            $sel = ['laki' => 0, 'perempuan' => 0, 'total' => 0];
            foreach ($wilayahIds as $wid) {
                $sel['laki']      += $nilai[$wid][$label]['laki'] ?? 0;
                $sel['perempuan'] += $nilai[$wid][$label]['perempuan'] ?? 0;
                $sel['total']     += $nilai[$wid][$label]['total'] ?? 0;
            }

            foreach ($this->urutSel($sel) as $v) {
                $row[] = $v;
            }

            $tot['laki']      += $sel['laki'];
            $tot['perempuan'] += $sel['perempuan'];
            $tot['total']     += $sel['total'];
        }

        if ($this->adaJumlahSeluruhnya) {
            foreach ($this->urutSel($tot) as $v) {
                $row[] = $v;
            }
        }

        return $row;
    }

    /** Nilai satu kategori sesuai sub-kolom aktif: [L?][P?][Total]. */
    private function urutSel(array $sel): array
    {
        if (! $this->lp) {
            return [$sel['total']];
        }

        $out = [];
        if ($this->adaLaki) {
            $out[] = $sel['laki'];
        }
        if ($this->adaPerempuan) {
            $out[] = $sel['perempuan'];
        }
        $out[] = $sel['total'];

        return $out;
    }

    // ── Data & struktur wilayah ─────────────────────────────────────────────

    /** @return array{0: array<int, array<string, array<string, int>>>, 1: string} */
    private function kumpulkanNilai(int $waktuId): array
    {
        $jenisSet = DataAgregatExport::punyaRincianJk($this->jenis)
            ? [$this->jenis, $this->jenis.'_l', $this->jenis.'_p']
            : [$this->jenis];

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
                if ($this->adaSubtotal) {
                    $out[] = ['tipe' => 'subtotal', 'nama' => $namaKec, 'ids' => $ids];
                }
                $semua = array_merge($semua, $ids);
            }

            if ($this->adaTotalKota && ! $this->kecamatan) {
                $out[] = ['tipe' => 'total', 'nama' => $this->labelTotalKota, 'ids' => $semua];
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
            if ($this->adaTotalKota && ! $this->kecamatan && $kec->count() > 1) {
                $out[] = ['tipe' => 'total', 'nama' => $this->labelTotalKota, 'ids' => $ids];
            }

            return $out;
        }

        $kota = DimWilayah::query()->where('is_kota', true)->first(['id']);
        if ($kota) {
            $out[] = ['tipe' => 'total', 'nama' => $this->labelTotalKota, 'ids' => [$kota->id]];
        }

        return $out;
    }

    /**
     * Urutan kategori (kolom). Bila SEMUA label diawali angka → urut numerik;
     * selain itu → urutan posisi di konfigurasi_import (= urutan kolom DKB),
     * lalu abjad.
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

        if ($label->every(fn ($l) => preg_match('/^\s*(umur\s+)?\d/i', (string) $l))) {
            return $label
                ->sortBy(fn ($l) => (int) (preg_match('/-?\d+/', (string) $l, $m) ? $m[0] : 0))
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

                // Kolom data (setelah kolom identitas) → format ribuan.
                $awalData = $this->huruf($this->kolIdentitas + 1);
                $sheet->getStyle($awalData.'1:'.$lastCol.$lastRow)
                    ->getNumberFormat()->setFormatCode('#,##0');

                $kolWilayah = $this->huruf($this->kolIdentitas);
                $sheet->getStyle($kolWilayah.'1:'.$kolWilayah.$lastRow)->getAlignment()->setWrapText(true);
            },
        ];
    }

    private function huruf(int $indeks1): string
    {
        return Coordinate::stringFromColumnIndex($indeks1);
    }
}
