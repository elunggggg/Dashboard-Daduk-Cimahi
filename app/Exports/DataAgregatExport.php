<?php

namespace App\Exports;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\KonfigurasiExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor data agregat ke Excel/PDF. Kolom, urutan, label header, dan format
 * angka DIAMBIL DARI tabel konfigurasi_export (dikelola Petugas lewat halaman
 * "Konfigurasi Export") — bukan lagi hardcode di sini.
 *
 * Baris keluaran diseragamkan menjadi array asosiatif [kunci_kolom => nilai]
 * lewat barisTampil() supaya Excel (map()) dan PDF (blade) memakai sumber yang
 * sama persis.
 */
class DataAgregatExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    /** Cache per-request: apakah sebuah jenis_indikator punya pasangan _l / _p. */
    private static array $cekRincianJk = [];

    private ?Collection $kolomAktifCache = null;

    public function __construct(
        private readonly ?int $waktuId = null,
        private readonly ?string $kecamatan = null,
        private readonly ?string $jenisIndikator = null,
    ) {
    }

    /**
     * True bila indikator terpilih punya rincian jenis kelamin ({jenis}_l &
     * {jenis}_p) — ekspornya lalu ikut memuat kolom "Laki-laki"/"Perempuan"
     * (satu baris per wilayah+periode+kategori), bukan satu baris per angka.
     */
    public static function punyaRincianJk(?string $jenis): bool
    {
        if ($jenis === null || $jenis === '' || str_ends_with($jenis, '_l') || str_ends_with($jenis, '_p')) {
            return false;
        }

        return self::$cekRincianJk[$jenis] ??= DimKategori::query()
            ->whereIn('jenis_indikator', [$jenis.'_l', $jenis.'_p'])
            ->distinct()
            ->count('jenis_indikator') === 2;
    }

    /**
     * Kolom berkas PDF (tata letak rata). Enam kolom identitas selalu ada
     * (baku); kolom Laki-laki / Perempuan / Jumlah label & tampil-tidaknya
     * mengikuti "Konfigurasi Unduh" (elemen yang sama dipakai berkas Excel).
     *
     * @return Collection<int, object{kunci: string, label: string, angka: bool}>
     */
    public function kolomAktif(): Collection
    {
        if ($this->kolomAktifCache !== null) {
            return $this->kolomAktifCache;
        }

        $lp = static::punyaRincianJk($this->jenisIndikator);

        $kolom = collect([
            (object) ['kunci' => 'kode_wilayah', 'label' => 'Kode Wilayah', 'angka' => false],
            (object) ['kunci' => 'kecamatan',    'label' => 'Kecamatan',    'angka' => false],
            (object) ['kunci' => 'kelurahan',    'label' => 'Kelurahan',    'angka' => false],
            (object) ['kunci' => 'periode',      'label' => 'Periode',      'angka' => false],
            (object) ['kunci' => 'indikator',    'label' => 'Indikator',    'angka' => false],
            (object) ['kunci' => 'kategori',     'label' => 'Kategori',     'angka' => false],
        ]);

        if ($lp && KonfigurasiExport::elemenAktif('laki')) {
            $kolom->push((object) ['kunci' => 'laki', 'label' => KonfigurasiExport::labelElemen('laki'), 'angka' => true]);
        }
        if ($lp && KonfigurasiExport::elemenAktif('perempuan')) {
            $kolom->push((object) ['kunci' => 'perempuan', 'label' => KonfigurasiExport::labelElemen('perempuan'), 'angka' => true]);
        }
        $kolom->push((object) ['kunci' => 'jumlah', 'label' => KonfigurasiExport::labelElemen('jumlah'), 'angka' => true]);

        return $this->kolomAktifCache = $kolom;
    }

    // ── Sumber baris (dipakai Excel & PDF) ───────────────────────────────────

    public function collection(): Collection
    {
        return $this->barisTampil();
    }

    /**
     * Baris siap tampil: array asosiatif [kunci_kolom => nilai], HANYA kolom
     * aktif, dalam urutan konfigurasi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function barisTampil(): Collection
    {
        $kolom = $this->kolomAktif();

        return $this->barisPenuh()->map(function (array $penuh) use ($kolom) {
            $out = [];
            foreach ($kolom as $k) {
                $out[$k->kunci] = $penuh[$k->kunci] ?? null;
            }

            return $out;
        })->values();
    }

    /**
     * Setiap baris dengan SELURUH field yang mungkin (9 kunci) terisi — proyeksi
     * ke kolom aktif dikerjakan barisTampil().
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function barisPenuh(): Collection
    {
        if (static::punyaRincianJk($this->jenisIndikator)) {
            return $this->barisRincianJk();
        }

        return static::query($this->waktuId, $this->kecamatan, $this->jenisIndikator)->get()
            ->map(fn (DataAgregat $r) => [
                'kode_wilayah' => $r->wilayah->kode_kemendagri ?? '—',
                'kecamatan'    => $r->wilayah->nama_kecamatan ?? '—',
                'kelurahan'    => $r->wilayah->nama_kelurahan ?? '—',
                'periode'      => $r->waktu->label ?? '—',
                'indikator'    => $r->kategori->jenis_indikator ?? '—',
                'kategori'     => $r->kategori->label ?? '—',
                'laki'         => null,
                'perempuan'    => null,
                'jumlah'       => (int) $r->jumlah,
            ]);
    }

    /**
     * Ekspor "L/P": gabungkan baris {jenis}, {jenis}_l, {jenis}_p menjadi SATU
     * baris per (wilayah, periode, kategori) dengan angka Laki-laki / Perempuan
     * / Jumlah.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function barisRincianJk(): Collection
    {
        $jenisSet = [$this->jenisIndikator, $this->jenisIndikator.'_l', $this->jenisIndikator.'_p'];

        $rows = DataAgregat::query()
            ->with([
                'wilayah:id,kode_kemendagri,nama_kelurahan,nama_kecamatan',
                'waktu:id,label',
                'kategori:id,jenis_indikator,label',
            ])
            ->whereHas('kategori', fn ($k) => $k->whereIn('jenis_indikator', $jenisSet))
            ->when($this->waktuId, fn ($q) => $q->where('waktu_id', $this->waktuId))
            ->when($this->kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($w) => $w->where('nama_kecamatan', $this->kecamatan)))
            ->get();

        $kolom = fn (string $j) => str_ends_with($j, '_l') ? 'laki'
            : (str_ends_with($j, '_p') ? 'perempuan' : 'jumlah');

        return $rows
            ->groupBy(fn ($r) => $r->wilayah_id.'|'.$r->waktu_id.'|'.$r->kategori->label)
            ->map(function (Collection $grup) use ($kolom) {
                $acuan = $grup->first();
                $baris = [
                    'kode_wilayah' => $acuan->wilayah->kode_kemendagri ?? '—',
                    'kecamatan'    => $acuan->wilayah->nama_kecamatan ?? '—',
                    'kelurahan'    => $acuan->wilayah->nama_kelurahan ?? '—',
                    'periode'      => $acuan->waktu->label ?? '—',
                    'indikator'    => $this->jenisIndikator,
                    'kategori'     => $acuan->kategori->label ?? '—',
                    'laki'         => 0,
                    'perempuan'    => 0,
                    'jumlah'       => 0,
                ];

                foreach ($grup as $r) {
                    $baris[$kolom($r->kategori->jenis_indikator)] = (int) $r->jumlah;
                }

                return $baris;
            })
            ->sortBy([
                fn ($a) => $a['kecamatan'],
                fn ($a) => $a['kelurahan'],
                fn ($a) => (int) filter_var($a['kategori'], FILTER_SANITIZE_NUMBER_INT),
                fn ($a) => $a['kategori'],
            ])
            ->values();
    }

    /**
     * Query mentah (baris data_agregat) — dipakai EksporController untuk
     * menghitung total baris sebelum memutuskan format & batas PDF.
     */
    public static function query(?int $waktuId, ?string $kecamatan, ?string $jenisIndikator)
    {
        return DataAgregat::query()
            ->with(['wilayah:id,kode_kemendagri,nama_kelurahan,nama_kecamatan', 'waktu:id,label', 'kategori:id,jenis_indikator,label'])
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->when($kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($w) => $w->where('nama_kecamatan', $kecamatan)))
            ->when($jenisIndikator, fn ($q) => $q->whereHas('kategori', fn ($k) => $k->where('jenis_indikator', $jenisIndikator)))
            ->join('dim_wilayah', 'dim_wilayah.id', '=', 'data_agregat.wilayah_id')
            ->join('dim_kategori', 'dim_kategori.id', '=', 'data_agregat.kategori_id')
            ->orderBy('dim_wilayah.nama_kecamatan')
            ->orderBy('dim_wilayah.nama_kelurahan')
            ->orderBy('dim_kategori.jenis_indikator')
            ->orderBy('dim_kategori.label')
            ->select('data_agregat.*');
    }

    // ── Bentuk berkas ───────────────────────────────────────────────────────

    public function headings(): array
    {
        return $this->kolomAktif()->pluck('label')->all();
    }

    /** $row selalu array [kunci => nilai] hasil barisTampil() — sudah urut kolom. */
    public function map($row): array
    {
        return array_values($row);
    }

    public function title(): string
    {
        return 'Data Agregat';
    }

    /** Header baris 1: putih tebal di atas navy brand (#1E3A5F), rata tengah vertikal. */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    /** Kolom angka → format ribuan. (Kelas ini kini hanya dipakai untuk PDF;
     *  concern Excel dibiarkan tetap benar seandainya dipakai lagi.) */
    public function columnFormats(): array
    {
        $formats = [];
        $huruf = 'A';

        foreach ($this->kolomAktif() as $k) {
            if ($k->angka) {
                $formats[$huruf] = '#,##0';
            }

            $huruf++;
        }

        return $formats;
    }

    /** Border tipis seluruh tabel + baris header dibekukan + autofilter. */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('D1D5DB');

                $sheet->getRowDimension(1)->setRowHeight(20);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}1");
            },
        ];
    }
}
