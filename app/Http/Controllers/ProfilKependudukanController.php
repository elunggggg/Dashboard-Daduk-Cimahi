<?php

namespace App\Http\Controllers;

use App\Models\DataAgregat;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use App\Services\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Ekspor "Buku Profil Kependudukan" — laporan PDF meniru struktur buku resmi
 * (docs/Profil_Kependudukan) yang disusun Disdukcapil setiap tahun dari data
 * Semester II. Publik & Petugas sama-sama bisa mengunduhnya (mirip Ekspor
 * data mentah yang sudah ada), memakai PERIODE TERBARU yang tersedia di
 * sistem — bukan berarti selalu Semester II, karena kita cuma punya periode
 * yang benar-benar sudah diimpor.
 *
 * KEJUJURAN DATA: buku resminya memuat tren multi-tahun (2022-2025), SMAM,
 * dan beberapa tabel lain yang datanya TIDAK ADA di sistem ini (cuma import
 * periode yang sudah diunggah Petugas). Tabel semacam itu tetap muncul
 * dengan judul yang sama supaya strukturnya mengikuti buku asli, tapi
 * isinya jujur menyatakan "Data tidak tersedia" alih-alih dikosongkan diam-
 * diam atau diisi angka karangan.
 */
class ProfilKependudukanController extends Controller
{
    private const AGE_ORDER = [
        '0-4 Tahun', '5-9 Tahun', '10-14 Tahun', '15-19 Tahun',
        '20-24 Tahun', '25-29 Tahun', '30-34 Tahun', '35-39 Tahun',
        '40-44 Tahun', '45-49 Tahun', '50-54 Tahun', '55-59 Tahun',
        '60-64 Tahun', '65-69 Tahun', '70-74 Tahun', '75+ Tahun',
    ];

    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function unduh(Request $request): Response
    {
        $waktu = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->first();

        abort_if($waktu === null, 404, 'Belum ada periode data yang diimpor.');

        $wilayahList = DimWilayah::kelurahan()->orderBy('nama_kecamatan')->orderBy('nama_kelurahan')->get();
        $waktuId     = $waktu->id;

        // ---- Bab III: Demografi ----
        $penduduk       = $this->perWilayah('jenis_kelamin', $waktuId, $wilayahList);
        $kepadatan      = $this->perWilayah('kepadatan_penduduk', $waktuId, $wilayahList);
        $lpp            = $this->perWilayah('lpp', $waktuId, $wilayahList);
        $kelUmur        = $this->perWilayah('kelompok_umur', $waktuId, $wilayahList);
        $rasioKelamin   = $this->perWilayah('rasio_jenis_kelamin', $waktuId, $wilayahList);
        $ketergantungan = $this->perWilayah('rasio_ketergantungan', $waktuId, $wilayahList);
        $statKawin      = $this->perWilayah('status_kawin', $waktuId, $wilayahList);
        $umurMedian     = $this->perWilayah('umur_median', $waktuId, $wilayahList);
        $usia0          = $this->perWilayah('kelahiran_proxy', $waktuId, $wilayahList);
        $perempuanSubur = $this->perWilayah('perempuan_usia_subur', $waktuId, $wilayahList);

        $piramida = $this->piramidaUmur($waktuId);

        // ---- Bab IV: Kualitas Penduduk ----
        $pendidikan  = $this->perWilayah('pendidikan', $waktuId, $wilayahList);
        $pekerjaan   = $this->perWilayah('pekerjaan', $waktuId, $wilayahList);
        $angkatanKerja = $this->perWilayah('angkatan_kerja', $waktuId, $wilayahList);
        $kk            = $this->perWilayah('kepemilikan_kk', $waktuId, $wilayahList);
        $kkJk          = $this->perWilayah('kepala_keluarga_jk', $waktuId, $wilayahList);
        $shbkel        = $this->perWilayah('status_hubungan_keluarga', $waktuId, $wilayahList);
        $agama         = $this->perWilayah('agama', $waktuId, $wilayahList);
        $disabilitas   = $this->perWilayah('disabilitas', $waktuId, $wilayahList);
        $golDarah      = $this->perWilayah('golongan_darah', $waktuId, $wilayahList);

        // ---- Bab V: Mobilitas ----
        $datang = $this->perWilayah('mobilitas_datang', $waktuId, $wilayahList);
        $pindah = $this->perWilayah('mobilitas_pindah', $waktuId, $wilayahList);
        $rasioPindahDatang = $this->perWilayah('rasio_pindah_datang', $waktuId, $wilayahList);

        // ---- Bab VI: Kepemilikan Dokumen ----
        $ktp        = $this->perWilayah('kepemilikan_ktp', $waktuId, $wilayahList);
        $kia        = $this->perWilayah('kepemilikan_kia', $waktuId, $wilayahList);
        $aktaLahir  = $this->perWilayah('akta_lahir', $waktuId, $wilayahList);
        $aktaLahir05 = $this->perWilayah('akta_lahir_0_5', $waktuId, $wilayahList);
        $aktaLahir017 = $this->perWilayah('akta_lahir_0_17', $waktuId, $wilayahList);
        $aktaKawin  = $this->perWilayah('akta_kawin', $waktuId, $wilayahList);
        $aktaCerai  = $this->perWilayah('akta_cerai', $waktuId, $wilayahList);
        $wna        = $this->perWilayah('wna', $waktuId, $wilayahList);

        // ---- Angka turunan se-kota (CBR/GFR/TPAK/rasio ketergantungan) ----
        $totalPenduduk = $penduduk->pluck('Laki-laki')->sum() + $penduduk->pluck('Perempuan')->sum();
        $totalUsia0    = $usia0->pluck('Jumlah Penduduk Usia 0 Tahun')->sum();
        $totalSubur    = collect($perempuanSubur)->flatten(1)->sum();
        $cbr = $totalPenduduk > 0 ? round($totalUsia0 / $totalPenduduk * 1000, 2) : null;
        $gfr = $totalSubur > 0 ? round($totalUsia0 / $totalSubur * 1000, 2) : null;

        $totalUsiaKerja  = $angkatanKerja->pluck('Jumlah Penduduk Usia Kerja')->sum();
        $totalAngkatanKerja = $angkatanKerja->pluck('Angkatan Kerja')->sum();
        $tpak = $totalUsiaKerja > 0 ? round($totalAngkatanKerja / $totalUsiaKerja * 100, 2) : null;

        $totalMuda = $ketergantungan->pluck('Usia Muda (0-14 Tahun)')->sum();
        $totalTua  = $ketergantungan->pluck('Usia Tua (65+ Tahun)')->sum();
        $totalProduktif = $ketergantungan->pluck('Usia Produktif (15-64 Tahun)')->sum();
        $dependencyRatio = $totalProduktif > 0 ? round(($totalMuda + $totalTua) / $totalProduktif * 100, 2) : null;

        $pdf = Pdf::loadView('laporan.profil-pdf', compact(
            'waktu', 'wilayahList',
            'penduduk', 'kepadatan', 'lpp', 'kelUmur', 'rasioKelamin', 'ketergantungan',
            'statKawin', 'umurMedian', 'usia0', 'perempuanSubur', 'piramida',
            'pendidikan', 'pekerjaan', 'angkatanKerja', 'kk', 'kkJk', 'shbkel',
            'agama', 'disabilitas', 'golDarah',
            'datang', 'pindah', 'rasioPindahDatang',
            'ktp', 'kia', 'aktaLahir', 'aktaLahir05', 'aktaLahir017', 'aktaKawin', 'aktaCerai', 'wna',
            'totalPenduduk', 'cbr', 'gfr', 'tpak', 'dependencyRatio',
        ))->setPaper('a4', 'portrait');

        $this->audit->record(AuditLogService::AKSI_EKSPOR, 'laporans', null, [
            'judul' => "Buku Profil Kependudukan — {$waktu->label}",
            'jenis' => 'pdf-profil',
        ]);

        return $pdf->download('profil-kependudukan-cimahi-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * Nilai per kelurahan untuk satu jenis_indikator, dikelompokkan
     * wilayah_id => [label => jumlah]. Kelurahan tanpa data untuk label
     * tertentu tidak muncul sama sekali di array labelnya (view yang
     * menampilkan "—" untuk yang tidak ada).
     *
     * @return Collection<int, array<string, int>>
     */
    private function perWilayah(string $jenis, int $waktuId, Collection $wilayahList): Collection
    {
        $rows = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis))
            ->where('waktu_id', $waktuId)
            ->whereIn('wilayah_id', $wilayahList->pluck('id'))
            ->with('kategori:id,label,urutan')
            ->get();

        $hasil = [];

        foreach ($rows as $row) {
            $hasil[$row->wilayah_id][$row->kategori->label] = (int) $row->jumlah;
        }

        return collect($hasil);
    }

    /**
     * Piramida penduduk se-kota: kelompok umur × jenis kelamin.
     *
     * KelompokUmur hanya menyimpan TOTAL (L+P), bukan per jenis kelamin —
     * jadi piramida di sini memakai rasio jenis kelamin KOTA (bukan per
     * kelompok umur) untuk memecah TOTAL setiap kelompok jadi estimasi L/P.
     * Ini SENGAJA dinyatakan di tabel sebagai "estimasi", bukan data mentah,
     * karena bukan itu yang tersimpan di data_agregat.
     *
     * @return array<int, array{label:string, laki:int, perempuan:int}>
     */
    private function piramidaUmur(int $waktuId): array
    {
        $totalUmur = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', 'kelompok_umur'))
            ->where('waktu_id', $waktuId)
            ->with('kategori:id,label')
            ->get()
            ->groupBy('kategori.label')
            ->map(fn ($rows) => $rows->sum('jumlah'));

        $jk = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', 'jenis_kelamin'))
            ->where('waktu_id', $waktuId)
            ->with('kategori:id,label')
            ->get()
            ->groupBy('kategori.label')
            ->map(fn ($rows) => $rows->sum('jumlah'));

        $totalL = $jk->get('Laki-laki', 0);
        $totalP = $jk->get('Perempuan', 0);
        $totalLP = $totalL + $totalP;
        $pctL = $totalLP > 0 ? $totalL / $totalLP : 0.5;

        $out = [];

        foreach (self::AGE_ORDER as $label) {
            $total = (int) ($totalUmur->get($label) ?? 0);
            $out[] = [
                'label'     => $label,
                'laki'      => (int) round($total * $pctL),
                'perempuan' => $total - (int) round($total * $pctL),
            ];
        }

        return $out;
    }
}
