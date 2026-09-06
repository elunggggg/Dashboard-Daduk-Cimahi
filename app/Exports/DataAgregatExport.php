<?php

namespace App\Exports;

use App\Models\DataAgregat;
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

class DataAgregatExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(
        private readonly ?int $waktuId = null,
        private readonly ?string $kecamatan = null,
        private readonly ?string $jenisIndikator = null,
    ) {
    }

    public function collection(): Collection
    {
        return static::query($this->waktuId, $this->kecamatan, $this->jenisIndikator)->get();
    }

    /**
     * Dipakai bersama oleh ekspor Excel dan PDF supaya angka keduanya identik.
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

    public function headings(): array
    {
        return ['Kode Wilayah', 'Kecamatan', 'Kelurahan', 'Periode', 'Indikator', 'Kategori', 'Jumlah'];
    }

    public function map($row): array
    {
        return [
            $row->wilayah->kode_kemendagri ?? '—',
            $row->wilayah->nama_kecamatan ?? '—',
            $row->wilayah->nama_kelurahan ?? '—',
            $row->waktu->label ?? '—',
            $row->kategori->jenis_indikator ?? '—',
            $row->kategori->label ?? '—',
            (int) $row->jumlah,
        ];
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

    /** Kolom "Jumlah" (G) pakai format ribuan bawaan Excel (tanpa desimal — ini
     *  cacah jiwa, bukan uang), bukan teks polos. */
    public function columnFormats(): array
    {
        return ['G' => '#,##0'];
    }

    /** Border tipis seluruh tabel + baris header dibekukan + autofilter — dikerjakan
     *  lewat event (bukan concern statis) karena butuh tahu jumlah baris/kolom akhir. */
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
