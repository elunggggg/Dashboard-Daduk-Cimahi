<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use App\Services\FilterWilayahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Satu endpoint yang memasok SELURUH data halaman Dashboard Publik (peta, KPI, umur,
 * sosial, status, kelompok, dokumen) sekaligus, supaya Alpine bisa
 * memperbarui semua grafik dalam satu fetch tanpa reload halaman.
 *
 * Filter: kecamatan, kelurahan, dan periode (waktu_id). Tanpa waktu_id,
 * jatuh ke semester terbaru — angka penduduk/KK/KTP dkk adalah STOK, jadi
 * "Semua Periode" tidak berarti apa-apa di sini (beda dari Mobilitas yang
 * angkanya arus).
 */
class DashboardDataController extends Controller
{
    /**
     * Beberapa indikator yang diminta layout (piramida umur per L/P, usia
     * wajib KTP per L/P, hubungan keluarga, lansia per L/P, anak per L/P)
     * TIDAK ada di skema data saat ini — importer DKB hanya menyimpan kolom
     * TOTAL untuk KelompokUmur (offset 2), bukan L dan P terpisah, dan sheet
     * "hubungan dalam keluarga" tidak pernah dipetakan sama sekali.
     *
     * Untuk indikator yang punya total riil tapi tidak punya rincian L/P,
     * rincian itu DITAKSIR memakai rasio L/P kota (atau wilayah terpilih) —
     * ditandai "estimasi" di response supaya front-end bisa memberi label
     * yang jujur. Untuk yang totalnya juga tidak ada sama sekali (hubungan
     * keluarga), responsnya `null` dan front-end menampilkan pesan
     * "Belum ada data".
     */
    public function __invoke(Request $request, FilterWilayahService $filter): JsonResponse
    {
        $validated = $request->validate([
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kelurahan' => ['nullable', 'integer', 'exists:dim_wilayah,id'],
            'waktu_id'  => ['nullable', 'integer', 'exists:dim_waktu,id'],
        ]);

        $kecamatan   = ($validated['kecamatan'] ?? null) ?: null;
        $kelurahanId = $validated['kelurahan'] ?? null;

        $kelurahanTerpilih = $kelurahanId ? DimWilayah::find($kelurahanId) : null;
        if ($kelurahanTerpilih) {
            $kecamatan = $kelurahanTerpilih->nama_kecamatan;
        }

        $waktuTerpilih = ! empty($validated['waktu_id']) ? DimWaktu::find($validated['waktu_id']) : null;
        $latestWaktu   = $waktuTerpilih ?? $filter->getLatestWaktu();

        if (! $latestWaktu) {
            return response()->json(['ada_data' => false]);
        }

        $waktuId = $latestWaktu->id;

        $totalPendudukKota = (int) DataAgregat::whereIn('kategori_id', DimKategori::where('jenis_indikator', 'jenis_kelamin')->pluck('id'))
            ->where('waktu_id', $waktuId)
            ->sum('jumlah');

        if ($totalPendudukKota === 0) {
            return response()->json(['ada_data' => false]);
        }

        $genderKota = $this->aggr('jenis_kelamin', $waktuId, null, null);
        $lakiKota   = (int) $genderKota->get('Laki-laki', 0);
        $prKota     = (int) $genderKota->get('Perempuan', 0);
        $rasioKota  = $totalPendudukKota > 0 ? $lakiKota / $totalPendudukKota : 0.5;

        $genderData = $this->aggr('jenis_kelamin', $waktuId, $kelurahanId, $kecamatan);
        $totalPenduduk = (int) $genderData->sum();
        $laki      = (int) $genderData->get('Laki-laki', 0);
        $perempuan = (int) $genderData->get('Perempuan', 0);
        // Rasio lokal dipakai untuk menaksir L/P pada indikator yang tidak
        // punya rincian gender sendiri, biar taksirannya mengikuti wilayah
        // yang sedang difilter, bukan selalu rasio kota.
        $rasioLokal = $totalPenduduk > 0 ? $laki / $totalPenduduk : $rasioKota;

        $ageData = $this->aggr('kelompok_umur', $waktuId, $kelurahanId, $kecamatan)
            ->sortBy(fn ($v, $k) => array_search($k, self::AGE_ORDER))
            ->filter(fn ($v, $k) => in_array($k, self::AGE_ORDER, true));

        $kkTotal   = (int) $this->aggr('kepemilikan_kk', $waktuId, $kelurahanId, $kecamatan)->get('Jumlah Kepala Keluarga', 0);
        $kkJkData  = $this->aggr('kepala_keluarga_jk', $waktuId, $kelurahanId, $kecamatan);
        $ktpData   = $this->aggr('kepemilikan_ktp', $waktuId, $kelurahanId, $kecamatan);
        $wajibKtp  = (int) $ktpData->get('Wajib KTP', 0);

        $kiaData     = $this->aggr('kepemilikan_kia', $waktuId, $kelurahanId, $kecamatan);
        $anakTotal   = (int) $kiaData->get('Jumlah Anak Usia 0-17 Tahun', 0);

        $disabilData = $this->aggr('disabilitas', $waktuId, $kelurahanId, $kecamatan);

        $lansiaTotal = (int) $ageData->filter(fn ($v, $k) => in_array($k, self::LANSIA_BANDS, true))->sum();

        $produktifData = $ageData->filter(fn ($v, $k) => in_array($k, self::PRODUKTIF_BANDS, true));

        $wajibKtpUmurData = $ageData->filter(fn ($v, $k) => in_array($k, self::WAJIB_KTP_BANDS, true));

        return response()->json([
            'ada_data' => true,
            'sumber' => "Data Kependudukan Berdasarkan Kemendagri Semester {$latestWaktu->semester} Tahun {$latestWaktu->tahun}",
            'filter' => [
                'kecamatan' => $kecamatan,
                'kelurahan_id' => $kelurahanId,
                'kelurahan_nama' => $kelurahanTerpilih?->nama_kelurahan,
            ],

            'peta' => [
                'kecamatan' => $this->pendudukPerKecamatan($waktuId),
                'kelurahan' => $this->pendudukPerKelurahan($waktuId),
            ],

            'kpi' => [
                'wajib_ktp' => $this->estimasiGender($wajibKtp, $rasioLokal, true),
                'penduduk'  => ['total' => $totalPenduduk, 'laki' => $laki, 'perempuan' => $perempuan, 'estimasi' => false],
                'kk'        => [
                    'total' => $kkTotal,
                    'laki' => (int) $kkJkData->get('Kepala Keluarga Laki-laki', 0),
                    'perempuan' => (int) $kkJkData->get('Kepala Keluarga Perempuan', 0),
                    'estimasi' => false,
                ],
            ],

            'umur' => [
                'piramida' => [
                    'labels'    => self::AGE_ORDER,
                    'laki'      => collect(self::AGE_ORDER)->map(fn ($l) => round(($ageData->get($l, 0)) * $rasioLokal))->values(),
                    'perempuan' => collect(self::AGE_ORDER)->map(fn ($l) => round(($ageData->get($l, 0)) * (1 - $rasioLokal)))->values(),
                    'estimasi'  => true,
                    'catatan'   => 'Rincian Laki-laki/Perempuan per kelompok umur belum diimpor dari DKB — nilai di sini ditaksir dari rasio L/P wilayah terpilih.',
                ],
                'produktif' => $this->keSeri($produktifData),
                'wajib_ktp_umur' => [
                    'labels'    => $wajibKtpUmurData->keys()->values(),
                    'laki'      => $wajibKtpUmurData->map(fn ($v) => round($v * $rasioLokal))->values(),
                    'perempuan' => $wajibKtpUmurData->map(fn ($v) => round($v * (1 - $rasioLokal)))->values(),
                    'estimasi'  => true,
                    'catatan'   => 'DKB tidak merinci status wajib-KTP per kelompok umur — grafik ini menaksir dari populasi usia ≥15 tahun (bukan angka wajib KTP sesungguhnya per kelompok).',
                ],
            ],

            'sosial' => [
                'pekerjaan'  => $this->keSeri($this->aggr('pekerjaan', $waktuId, $kelurahanId, $kecamatan)->sortDesc()->take(10)),
                'agama'      => $this->keSeri($this->aggr('agama', $waktuId, $kelurahanId, $kecamatan)),
                'pendidikan' => $this->keSeri($this->aggr('pendidikan', $waktuId, $kelurahanId, $kecamatan)),
            ],

            'status' => [
                'perkawinan' => $this->keSeri($this->aggr('status_kawin', $waktuId, $kelurahanId, $kecamatan)),
                'hubungan_keluarga' => null, // tidak pernah dipetakan importer — lihat catatan kelas.
                'golongan_darah' => $this->keSeri($this->aggr('golongan_darah', $waktuId, $kelurahanId, $kecamatan)),
            ],

            'kelompok' => [
                'anak' => $this->estimasiGender($anakTotal, $rasioLokal, true),
                'disabilitas' => $this->keSeri($disabilData),
                'lansia' => array_merge($this->estimasiGender($lansiaTotal, $rasioLokal, true), [
                    'catatan' => 'Lansia dihitung dari total kelompok umur 60+ (data riil); rincian L/P ditaksir dari rasio wilayah.',
                ]),
            ],

            'dokumen' => [
                'akta_lahir' => $this->perKelurahan('akta_lahir', 'Belum Memiliki Akta Lahir', $waktuId, $kelurahanId, $kecamatan),
                'kia'        => $this->perKelurahan('kepemilikan_kia', 'Belum Memiliki KIA', $waktuId, $kelurahanId, $kecamatan),
            ],

            // Tren se-Kota lintas SELURUH periode — SENGAJA tidak ikut filter
            // kecamatan/kelurahan (tren kota, sama pola dengan Tren Mobilitas).
            'tren' => $this->trenPenduduk(),

            // Dipakai tab "Data Penduduk" — rincian per kelurahan mengikuti
            // filter kecamatan/kelurahan yang sedang aktif (beda dari peta.*
            // yang selalu seluruh kota).
            'tabel' => $this->tabelKelurahan($waktuId, $kelurahanId, $kecamatan),
        ]);
    }

    private const AGE_ORDER = [
        '0-4 Tahun', '5-9 Tahun', '10-14 Tahun', '15-19 Tahun',
        '20-24 Tahun', '25-29 Tahun', '30-34 Tahun', '35-39 Tahun',
        '40-44 Tahun', '45-49 Tahun', '50-54 Tahun', '55-59 Tahun',
        '60-64 Tahun', '65-69 Tahun', '70-74 Tahun', '75+ Tahun',
    ];

    private const PRODUKTIF_BANDS = [
        '15-19 Tahun', '20-24 Tahun', '25-29 Tahun', '30-34 Tahun', '35-39 Tahun',
        '40-44 Tahun', '45-49 Tahun', '50-54 Tahun', '55-59 Tahun', '60-64 Tahun',
    ];

    private const WAJIB_KTP_BANDS = [
        '15-19 Tahun', '20-24 Tahun', '25-29 Tahun', '30-34 Tahun', '35-39 Tahun',
        '40-44 Tahun', '45-49 Tahun', '50-54 Tahun', '55-59 Tahun', '60-64 Tahun',
        '65-69 Tahun', '70-74 Tahun', '75+ Tahun',
    ];

    private const LANSIA_BANDS = ['60-64 Tahun', '65-69 Tahun', '70-74 Tahun', '75+ Tahun'];

    private function aggr(string $jenis, int $waktuId, ?int $wilayahId, ?string $kecamatan): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
            ->where('waktu_id', $waktuId)
            ->when($wilayahId, fn ($q) => $q->where('wilayah_id', $wilayahId))
            ->when(! $wilayahId && $kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($q) => $q->where('nama_kecamatan', $kecamatan)))
            ->with('kategori')
            ->get()
            ->sortBy('kategori.urutan')
            ->groupBy('kategori.label')
            ->map(fn ($rows) => (int) $rows->sum('jumlah'));
    }

    /** @return array{labels: array<int,string>, values: array<int,int>} */
    private function keSeri(Collection $data): array
    {
        return ['labels' => $data->keys()->values(), 'values' => $data->values()];
    }

    private function estimasiGender(int $total, float $rasioLaki, bool $estimasi): array
    {
        return [
            'total'     => $total,
            'laki'      => (int) round($total * $rasioLaki),
            'perempuan' => (int) round($total * (1 - $rasioLaki)),
            'estimasi'  => $estimasi,
        ];
    }

    /** Total penduduk per kecamatan — SELALU seluruh kota (tidak ikut filter) agar gradasi warna peta tetap sebanding. */
    private function pendudukPerKecamatan(int $waktuId): array
    {
        $pendudukKatIds = DimKategori::where('jenis_indikator', 'jenis_kelamin')->pluck('id');

        return DimWilayah::query()
            ->withSum(['dataAgregat as total' => fn ($q) => $q->whereIn('kategori_id', $pendudukKatIds)->where('waktu_id', $waktuId)], 'jumlah')
            ->get()
            ->groupBy('nama_kecamatan')
            ->map(fn ($rows) => (int) $rows->sum('total'))
            ->toArray();
    }

    /** Total + posisi ilustratif per kelurahan — SELALU seluruh kota (tidak ikut filter). */
    private function pendudukPerKelurahan(int $waktuId): array
    {
        $pendudukKatIds = DimKategori::where('jenis_indikator', 'jenis_kelamin')->pluck('id');
        $posisi = $this->posisiIlustratif();

        return DimWilayah::query()
            ->withSum(['dataAgregat as total' => fn ($q) => $q->whereIn('kategori_id', $pendudukKatIds)->where('waktu_id', $waktuId)], 'jumlah')
            ->orderBy('nama_kecamatan')->orderBy('nama_kelurahan')
            ->get()
            ->map(fn (DimWilayah $w) => [
                'id' => $w->id,
                'nama_kelurahan' => $w->nama_kelurahan,
                'nama_kecamatan' => $w->nama_kecamatan,
                'total' => (int) ($w->total ?? 0),
                'lat' => $posisi[$w->id][0] ?? null,
                'lng' => $posisi[$w->id][1] ?? null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Tidak ada batas GeoJSON per kelurahan (baru tersedia di tingkat
     * kecamatan — lihat public/geojson/cimahi.json), jadi posisi tiap
     * kelurahan ditaksir: pusat kecamatannya + jarak kecil melingkar
     * berdasarkan urutan id. ILUSTRATIF, bukan koordinat asli.
     *
     * @return array<int, array{0: float, 1: float}>
     */
    private function posisiIlustratif(): array
    {
        $pusatKecamatan = [
            'Cimahi Utara'   => [-6.858, 107.535],
            'Cimahi Tengah'  => [-6.892, 107.533],
            'Cimahi Selatan' => [-6.918, 107.535],
        ];

        $posisi = [];

        foreach (DimWilayah::kelurahan()->orderBy('nama_kecamatan')->orderBy('nama_kelurahan')->get(['id', 'nama_kecamatan']) as $i => $w) {
            $pusat = $pusatKecamatan[$w->nama_kecamatan] ?? [-6.885, 107.535];
            $dalamKecamatan = DimWilayah::kelurahan()->where('nama_kecamatan', $w->nama_kecamatan)->orderBy('nama_kelurahan')->pluck('id')->values();
            $idx = $dalamKecamatan->search($w->id);
            $n = max($dalamKecamatan->count(), 1);
            $sudut = ($idx / $n) * 2 * M_PI;
            $radius = 0.011;

            $posisi[$w->id] = [
                $pusat[0] + sin($sudut) * $radius,
                $pusat[1] + cos($sudut) * $radius,
            ];
        }

        return $posisi;
    }

    /** Rincian penduduk/KK/wajib-KTP per kelurahan — dipakai tab "Data Penduduk". */
    private function tabelKelurahan(int $waktuId, ?int $wilayahId, ?string $kecamatan): array
    {
        $lakiId = DimKategori::where('jenis_indikator', 'jenis_kelamin')->where('label', 'Laki-laki')->value('id');
        $prId   = DimKategori::where('jenis_indikator', 'jenis_kelamin')->where('label', 'Perempuan')->value('id');
        $kkId   = DimKategori::where('jenis_indikator', 'kepemilikan_kk')->where('label', 'Jumlah Kepala Keluarga')->value('id');
        $ktpId  = DimKategori::where('jenis_indikator', 'kepemilikan_ktp')->where('label', 'Wajib KTP')->value('id');

        return DimWilayah::query()
            ->when($wilayahId, fn ($q) => $q->where('id', $wilayahId))
            ->when(! $wilayahId && $kecamatan, fn ($q) => $q->where('nama_kecamatan', $kecamatan))
            ->withSum(['dataAgregat as laki' => fn ($q) => $q->where('kategori_id', $lakiId)->where('waktu_id', $waktuId)], 'jumlah')
            ->withSum(['dataAgregat as perempuan' => fn ($q) => $q->where('kategori_id', $prId)->where('waktu_id', $waktuId)], 'jumlah')
            ->withSum(['dataAgregat as kk' => fn ($q) => $q->where('kategori_id', $kkId)->where('waktu_id', $waktuId)], 'jumlah')
            ->withSum(['dataAgregat as wajib_ktp' => fn ($q) => $q->where('kategori_id', $ktpId)->where('waktu_id', $waktuId)], 'jumlah')
            ->orderBy('nama_kecamatan')->orderBy('nama_kelurahan')
            ->get()
            ->map(fn (DimWilayah $w) => [
                'id' => $w->id,
                'nama_kelurahan' => $w->nama_kelurahan,
                'nama_kecamatan' => $w->nama_kecamatan,
                'laki' => (int) ($w->laki ?? 0),
                'perempuan' => (int) ($w->perempuan ?? 0),
                'total' => (int) (($w->laki ?? 0) + ($w->perempuan ?? 0)),
                'kk' => (int) ($w->kk ?? 0),
                'wajib_ktp' => (int) ($w->wajib_ktp ?? 0),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Tren Jumlah Penduduk & Kepadatan Penduduk se-Kota lintas seluruh periode
     * di dim_waktu. Kepadatan = total penduduk / total luas seluruh kelurahan
     * (luas kota dianggap tetap antar periode — DKB tidak mencatat luas per
     * periode). Front-end menampilkan line kalau >1 periode, bar kalau cuma 1.
     */
    private function trenPenduduk(): array
    {
        $totalLuas = (float) DimWilayah::kelurahan()->sum('luas_km2');
        $pendudukKatIds = DimKategori::where('jenis_indikator', 'jenis_kelamin')->pluck('id');

        $periode = DimWaktu::orderBy('tahun')->orderBy('semester')->get();

        $penduduk = $periode->map(fn (DimWaktu $w) => (int) DataAgregat::whereIn('kategori_id', $pendudukKatIds)
            ->where('waktu_id', $w->id)
            ->sum('jumlah'));

        return [
            'labels'    => $periode->pluck('label')->values(),
            'penduduk'  => $penduduk->values(),
            'kepadatan' => $totalLuas > 0 ? $penduduk->map(fn ($p) => round($p / $totalLuas))->values() : $penduduk->map(fn () => 0)->values(),
        ];
    }

    /** Breakdown satu label kategori, per kelurahan — dipakai Bagian 10 (dokumen). */
    private function perKelurahan(string $jenis, string $label, int $waktuId, ?int $wilayahId, ?string $kecamatan): array
    {
        $rows = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->where('label', $label)->aktif())
            ->where('waktu_id', $waktuId)
            ->when($wilayahId, fn ($q) => $q->where('wilayah_id', $wilayahId))
            ->when(! $wilayahId && $kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($q) => $q->where('nama_kecamatan', $kecamatan)))
            ->with('wilayah:id,nama_kelurahan')
            ->get()
            ->sortByDesc('jumlah');

        return [
            'labels' => $rows->pluck('wilayah.nama_kelurahan')->values(),
            'values' => $rows->pluck('jumlah')->values(),
        ];
    }
}
