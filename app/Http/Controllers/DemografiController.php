<?php

namespace App\Http\Controllers;

use App\Models\DataAgregat;
use App\Models\DimWilayah;
use App\Services\FilterWilayahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DemografiController extends Controller
{
    private const AGE_ORDER = [
        '0-4 Tahun','5-9 Tahun','10-14 Tahun','15-19 Tahun',
        '20-24 Tahun','25-29 Tahun','30-34 Tahun','35-39 Tahun',
        '40-44 Tahun','45-49 Tahun','50-54 Tahun','55-59 Tahun',
        '60-64 Tahun','65-69 Tahun','70-74 Tahun','75+ Tahun',
    ];

    public function __construct(private FilterWilayahService $filter) {}

    public function __invoke(Request $request): View|JsonResponse
    {
        return app(\App\Services\DashboardHalaman::class)->tanggapi('demografi', $request);
    }

    /**
     * Menghitung SELURUH metrik + payload chart Demografi untuk satu kombinasi
     * filter. Dipisah dari __invoke supaya bisa dipakai ulang oleh halaman lain
     * (Sosial) ketika sebuah bagian Demografi dipindahkan ke sana lewat menu
     * "Bagian Dashboard". Mengembalikan ['vars' => [...], 'charts' => [...]].
     */
    public function hitung(?int $waktuId, ?int $wilayahId, ?string $kecamatan, $waktuList): array
    {
        $genderData   = $this->aggr('jenis_kelamin',  $waktuId, $wilayahId, $kecamatan);
        $ageData      = $this->aggr('kelompok_umur',  $waktuId, $wilayahId, $kecamatan)
            ->sortBy(fn ($v, $k) => array_search($k, self::AGE_ORDER));
        $maritalData  = $this->aggr('status_kawin',   $waktuId, $wilayahId, $kecamatan);
        $disabilData  = $this->aggr('disabilitas',    $waktuId, $wilayahId, $kecamatan);

        /*
         * Piramida penduduk — kelompok umur PER JENIS KELAMIN (lihat
         * KonfigurasiImportSeeder untuk asal datanya: kolom L/P sheet
         * KelompokUmur, ditambahkan 2026-09-06). Diurutkan sama seperti
         * $ageData supaya piramida & KPI Anak/Lansia di bawah konsisten.
         */
        $ageLakiData = $this->aggr('kelompok_umur_l', $waktuId, $wilayahId, $kecamatan)
            ->sortBy(fn ($v, $k) => array_search($k, self::AGE_ORDER));
        $agePerempuanData = $this->aggr('kelompok_umur_p', $waktuId, $wilayahId, $kecamatan)
            ->sortBy(fn ($v, $k) => array_search($k, self::AGE_ORDER));

        // Jumlah Anak (0-14 tahun) & Jumlah Penduduk Lansia (65+ tahun), per
        // jenis kelamin — dijumlahkan dari ageLakiData/agePerempuanData yang
        // sama dengan piramida di atas, supaya ikut filter kelurahan/periode
        // apa pun dan selalu konsisten dengan bentuk piramidanya.
        $kelompokAnak   = array_slice(self::AGE_ORDER, 0, 3);
        $kelompokLansia = array_slice(self::AGE_ORDER, 13, 3);
        $anakData = collect([
            'Laki-laki' => $ageLakiData->only($kelompokAnak)->sum(),
            'Perempuan' => $agePerempuanData->only($kelompokAnak)->sum(),
        ]);
        $lansiaData = collect([
            'Laki-laki' => $ageLakiData->only($kelompokLansia)->sum(),
            'Perempuan' => $agePerempuanData->only($kelompokLansia)->sum(),
        ]);

        $totalPenduduk = $genderData->sum();
        $laki          = $genderData->get('Laki-laki', 0);
        $perempuan     = $genderData->get('Perempuan', 0);
        $rasio         = $perempuan > 0 ? round($laki / $perempuan * 100, 1) : 0;

        // Kelompok produktif (15-64 tahun)
        $produktif = $ageData->filter(
            fn ($v, $k) => in_array($k, array_slice(self::AGE_ORDER, 3, 10))
        )->sum();

        /*
         * Usia muda (0-14) & usia tua (65+) diturunkan dari ageData yang sama
         * dengan $produktif di atas — BUKAN dari sheet RasioKetergantungan
         * yang diimpor, supaya angkanya taat pada filter kelurahan/kecamatan
         * apa pun (nilai sheet itu sendiri sudah benar per baris, tapi
         * menjumlahkannya lintas kelurahan tanpa menjumlahkan ulang dari
         * ageData akan konsisten dengan cara $produktif dihitung).
         */
        $usiaMuda = $ageData->filter(
            fn ($v, $k) => in_array($k, array_slice(self::AGE_ORDER, 0, 3))
        )->sum();
        $usiaTua = $ageData->filter(
            fn ($v, $k) => in_array($k, array_slice(self::AGE_ORDER, 13, 3))
        )->sum();
        $rasioKetergantungan = $produktif > 0
            ? round(($usiaMuda + $usiaTua) / $produktif * 100, 1)
            : 0;

        // Kepadatan: Luas Wilayah dijumlahkan dari sheet KepadatanPenduduk
        // sendiri (bukan dim_wilayah.luas_km2 — dua sumber itu beda angka
        // untuk kelurahan yang sama; yang dipakai di sini sengaja konsisten
        // dengan angka Kepadatan resmi Disdukcapil, bukan sumber lain), lalu
        // kepadatannya dihitung ulang dari totalPenduduk filter yang aktif
        // supaya sah untuk kombinasi kelurahan manapun.
        $kepadatanData = $this->aggr('kepadatan_penduduk', $waktuId, $wilayahId, $kecamatan);
        $luasWilayah   = $kepadatanData->get('Luas Wilayah (km2)', 0);
        $kepadatan     = $luasWilayah > 0 ? round($totalPenduduk / $luasWilayah) : 0;

        /*
         * CBR & GFR — DKB tidak punya sheet "jumlah kelahiran" tersendiri.
         * Proxy resminya (dipakai buku profil resmi juga): jumlah penduduk
         * usia 0 tahun. CBR = usia0 ÷ total penduduk × 1000. GFR = usia0 ÷
         * perempuan usia subur (15-49 tahun) × 1000.
         *
         * Angka usia 0 diketahui SELALU under-registrasi (bayi yang lahir
         * belum sempat lapor Disdukcapil) — buku profil resmi menyebutnya
         * eksplisit. Sengaja tidak "dikoreksi" di sini, ditampilkan apa
         * adanya dengan catatan di layar (lihat view), sama seperti sikap
         * buku profil sendiri.
         */
        $usia0Data          = $this->aggr('kelahiran_proxy', $waktuId, $wilayahId, $kecamatan);
        $usia0              = $usia0Data->get('Jumlah Penduduk Usia 0 Tahun', 0);
        $perempuanSuburData = $this->aggr('perempuan_usia_subur', $waktuId, $wilayahId, $kecamatan);
        $perempuanSubur     = $perempuanSuburData->sum();
        $cbr = $totalPenduduk > 0   ? round($usia0 / $totalPenduduk * 1000, 2)  : 0;
        $gfr = $perempuanSubur > 0  ? round($usia0 / $perempuanSubur * 1000, 2) : 0;

        /*
         * Umur tunggal (0-99 tahun) — sudah diimpor penuh dari sheet
         * UmurTunggal tapi belum pernah ditampilkan di halaman manapun
         * sampai sekarang. Usia 0 digabung dari 'kelahiran_proxy' (dipakai
         * ganda untuk CBR di atas) supaya deretnya utuh 0-99, diurutkan
         * SECARA NUMERIK dari teks labelnya — urutan() di dim_kategori
         * semuanya masih 0 (default), jadi tidak bisa diandalkan buat ini.
         */
        $umurTunggalData = $this->aggr('umur_tunggal', $waktuId, $wilayahId, $kecamatan);
        $umurTunggalData->put('Umur 0 Tahun', $usia0);
        $umurTunggalData = $umurTunggalData->sortBy(
            fn ($v, $k) => (int) filter_var($k, FILTER_SANITIZE_NUMBER_INT)
        );

        /*
         * Umur median & LPP: nilai per kelurahan yang diimpor APA ADANYA
         * (dihitung Disdukcapil sendiri, LPP malah butuh data S2 tahun lalu
         * yang tidak kita punya). Keduanya BUKAN hitungan — menjumlahkan
         * lintas kelurahan (seperti aggr() lain di file ini) tidak masuk
         * akal, jadi dipakai RATA-RATA lewat avgIndikator(), bukan aggr().
         */
        $umurMedian = $this->avgIndikator('umur_median', 'Umur Median', $waktuId, $wilayahId, $kecamatan);
        $lpp        = $this->avgIndikator('lpp', 'Laju Pertumbuhan Penduduk (%)', $waktuId, $wilayahId, $kecamatan);

        $wnaData  = $this->aggr('wna', $waktuId, $wilayahId, $kecamatan);
        $totalWna = $wnaData->get('WNA Jumlah', 0);

        /*
         * Status perkawinan menurut kelompok umur, PER KECAMATAN (Tabel 17) —
         * sheet Perkawinan_KU. Datanya sendiri memang cuma 3 baris kecamatan
         * (bukan 15 kelurahan) — lihat pseudo-wilayah is_kecamatan. Kalau
         * Petugas memilih kelurahan tertentu di filter, kecamatan yang
         * ditampilkan ikut mengikuti (diturunkan dari kelurahan itu);
         * kalau tidak memilih apa-apa, ketiga kecamatan ditampilkan semua.
         *
         * CATATAN JUJUR: angka pada sheet sumbernya SAMA PERSIS antara
         * berkas Semester I dan Semester II 2025 — kemungkinan besar
         * Disdukcapil belum memperbarui tabel ini antar-semester. Ditampilkan
         * apa adanya, bukan importer yang keliru (sudah diverifikasi manual
         * terhadap kedua berkas sumber).
         */
        $efektifKecamatan = $kecamatan ?: (
            $wilayahId ? DimWilayah::find($wilayahId)?->nama_kecamatan : null
        );
        $perkawinanKuJenis = [
            'perkawinan_ku_belum_kawin' => 'Belum Kawin',
            'perkawinan_ku_kawin' => 'Kawin',
            'perkawinan_ku_cerai_hidup' => 'Cerai Hidup',
            'perkawinan_ku_cerai_mati' => 'Cerai Mati',
        ];
        $perkawinanKuData = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->whereIn('jenis_indikator', array_keys($perkawinanKuJenis))->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->when($efektifKecamatan, fn ($q) => $q->whereHas('wilayah', fn ($q) => $q->where('nama_kecamatan', $efektifKecamatan)))
            ->with(['kategori', 'wilayah'])
            ->get()
            ->groupBy(fn ($row) => $row->wilayah->nama_kelurahan)
            ->map(fn ($rows) => $rows
                ->groupBy('kategori.jenis_indikator')
                ->map(fn ($g) => $g->sum('jumlah')));

        /*
         * ASFR (Age Specific Fertility Rate) & TFR — sheet ASFR bersifat
         * se-Kota (ORIENTASI_KOTA), jadi SENGAJA tidak ikut filter
         * kelurahan/kecamatan, sama seperti pola $terbitTahunan di
         * SosialController. Kolom "TFR" & "Jumlah Penduduk" bawaan sheet
         * TIDAK diimpor (lihat catatan seeder) — TFR di sini dihitung ulang
         * dari dua komponen yang sudah divalidasi: ASFR = kelahiran hidup ÷
         * jumlah perempuan × 1000 per kelompok umur, TFR = 5 × Σ ASFR ÷ 1000.
         */
        $asfrPerempuanData = $this->aggr('asfr_jumlah_perempuan', $waktuId, null, null);
        $asfrKelahiranData = $this->aggr('asfr_kelahiran_hidup', $waktuId, null, null);
        $asfrData = collect(['15-19 Tahun', '20-24 Tahun', '25-29 Tahun', '30-34 Tahun', '35-39 Tahun', '40-44 Tahun', '45-49 Tahun'])
            ->mapWithKeys(function ($band) use ($asfrPerempuanData, $asfrKelahiranData) {
                $perempuan = $asfrPerempuanData->get("Jumlah Perempuan {$band}", 0);
                $kelahiran = $asfrKelahiranData->get("Kelahiran Hidup {$band}", 0);
                $asfr = $perempuan > 0 ? round($kelahiran / $perempuan * 1000, 2) : 0;

                return [$band => $asfr];
            });
        $tfr = round($asfrData->sum() * 5 / 1000, 2);

        /*
         * Non-disabilitas dihitung, bukan diimpor.
         *
         * Berkas DKB hanya mendata penyandang disabilitas per jenis; tidak ada
         * kolom "tidak menyandang disabilitas" di sheet mana pun. Angkanya
         * diturunkan dari selisih terhadap jumlah penduduk pada filter yang
         * sedang aktif, sehingga selalu ikut berubah saat kelurahan atau
         * periodenya diganti.
         */
        $totalDisabilitas = (int) $disabilData->sum();
        $nonDisabilitas   = max(0, $totalPenduduk - $totalDisabilitas);
        $pctDisabilitas   = $totalPenduduk > 0
            ? round($totalDisabilitas / $totalPenduduk * 100, 2)
            : 0.0;

        /*
         * Penyandang disabilitas menurut pekerjaan (Tabel 22) — se-Kota.
         * disabilitas_pekerjaan_l/_p disimpan terpisah (sama seperti
         * kk_pendidikan) supaya dijumlahkan L+P di sini, bukan diimpor apa
         * adanya (posisi kolomnya sendiri bergeser antar-semester).
         */
        $disabPekerjaanL = $this->aggr('disabilitas_pekerjaan_l', $waktuId, null, null);
        $disabPekerjaanP = $this->aggr('disabilitas_pekerjaan_p', $waktuId, null, null);
        $disabilitasPekerjaanData = $disabPekerjaanL->keys()
            ->map(fn ($label) => str_replace(' (L)', '', $label))
            ->mapWithKeys(fn ($label) => [
                $label => $disabPekerjaanL->get("{$label} (L)", 0) + $disabPekerjaanP->get("{$label} (P)", 0),
            ])
            ->sortDesc();

        /*
         * Penyandang disabilitas usia sekolah PER KECAMATAN (Tabel 15) —
         * ditampilkan ringkas sebagai total per kecamatan (dijumlahkan
         * lintas 6 jenis disabilitas × 4 kelompok umur sekolah); rincian
         * penuhnya ada di Ekspor PDF Buku Profil.
         */
        $disabilitasUsklhData = DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', 'like', 'disabilitas_usklh_%')->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->with('wilayah')
            ->get()
            ->groupBy(fn ($row) => $row->wilayah->nama_kelurahan)
            ->map(fn ($rows) => $rows->sum('jumlah'));

        // Penyandang disabilitas menurut kelompok umur PER KECAMATAN (Tabel 36).
        $disabilitasKuData = $this->perKecamatan([
            'disabilitas_ku_fisik'         => 'Fisik',
            'disabilitas_ku_netra'         => 'Netra/Buta',
            'disabilitas_ku_rungu_wicara'  => 'Rungu/Wicara',
            'disabilitas_ku_mental'        => 'Mental/Jiwa',
            'disabilitas_ku_fisik_mental'  => 'Fisik dan Mental',
            'disabilitas_ku_lainnya'       => 'Lainnya',
        ], $waktuId);

        // Golongan darah menurut kelompok umur PER KECAMATAN (Tabel 40).
        $golDarKuData = $this->perKecamatan([
            'goldar_ku_a' => 'A', 'goldar_ku_b' => 'B', 'goldar_ku_ab' => 'AB', 'goldar_ku_o' => 'O',
            'goldar_ku_a_plus' => 'A+', 'goldar_ku_a_minus' => 'A-',
            'goldar_ku_b_plus' => 'B+', 'goldar_ku_b_minus' => 'B-',
            'goldar_ku_o_plus' => 'O+', 'goldar_ku_o_minus' => 'O-',
            'goldar_ku_ab_plus' => 'AB+', 'goldar_ku_ab_minus' => 'AB-',
        ], $waktuId);

        $selectedWaktu = $waktuList->firstWhere('id', $waktuId);

        $vars = [
            'totalPenduduk' => $totalPenduduk, 'laki' => $laki, 'perempuan' => $perempuan,
            'rasio' => $rasio, 'kepadatan' => $kepadatan, 'umurMedian' => $umurMedian,
            'lpp' => $lpp, 'totalWna' => $totalWna, 'selectedWaktu' => $selectedWaktu,
            'genderData' => $genderData, 'anakData' => $anakData, 'lansiaData' => $lansiaData,
            'maritalData' => $maritalData, 'disabilData' => $disabilData,
            'pctDisabilitas' => $pctDisabilitas, 'totalDisabilitas' => $totalDisabilitas, 'nonDisabilitas' => $nonDisabilitas,
            'ageData' => $ageData, 'produktif' => $produktif, 'usiaMuda' => $usiaMuda, 'usiaTua' => $usiaTua,
            'rasioKetergantungan' => $rasioKetergantungan, 'umurTunggalData' => $umurTunggalData,
            'usia0' => $usia0, 'cbr' => $cbr, 'gfr' => $gfr, 'tfr' => $tfr, 'asfrData' => $asfrData,
            'perkawinanKuData' => $perkawinanKuData, 'perkawinanKuJenis' => $perkawinanKuJenis,
            'disabilitasKuData' => $disabilitasKuData, 'golDarKuData' => $golDarKuData,
            'disabilitasPekerjaanData' => $disabilitasPekerjaanData, 'disabilitasUsklhData' => $disabilitasUsklhData,
        ];

        $seri = fn (Collection $d) => ['labels' => $d->keys()->values(), 'values' => $d->values()];

        $charts = [
            'gender'   => $seri($genderData),
            'anak'     => $seri($anakData),
            'lansia'   => $seri($lansiaData),
            'marital'  => $seri($maritalData),
            'disab'    => $seri($disabilData),
            'piramida' => [
                'labels'    => $ageLakiData->keys()->values(),
                'laki'      => $ageLakiData->values(),
                'perempuan' => $agePerempuanData->values(),
            ],
            'total_penduduk' => $totalPenduduk,
        ];

        return ['vars' => $vars, 'charts' => $charts];
    }

    /**
     * Total per kecamatan untuk sekumpulan jenis_indikator ORIENTASI_KECAMATAN
     * (Perkawinan_KU, Agama_KelUmur_Kec, GolDar_KelUmur_Kec, Disabilitas_KU,
     * KK_Kawin_KelUmur, dst) — hasilnya kecamatan => [label kategori => total].
     *
     * @param  array<string, string>  $jenisKeLabel  jenis_indikator => label tampilan
     */
    private function perKecamatan(array $jenisKeLabel, ?int $waktuId): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->whereIn('jenis_indikator', array_keys($jenisKeLabel))->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->with(['kategori', 'wilayah'])
            ->get()
            ->groupBy(fn ($row) => $row->wilayah->nama_kelurahan)
            ->map(fn ($rows) => $rows
                ->groupBy('kategori.jenis_indikator')
                ->mapWithKeys(fn ($g, $jenis) => [$jenisKeLabel[$jenis] => $g->sum('jumlah')]));
    }

    /** Rata-rata satu label — dipakai untuk nilai yang BUKAN hitungan (rasio/median). */
    private function avgIndikator(string $jenis, string $label, ?int $waktuId, ?int $wilayahId, ?string $kecamatan): float
    {
        return (float) (DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->where('label', $label)->aktif())
            ->when($waktuId,   fn ($q) => $q->where('waktu_id', $waktuId))
            ->when($wilayahId, fn ($q) => $q->where('wilayah_id', $wilayahId))
            ->when(! $wilayahId && $kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($q) => $q->where('nama_kecamatan', $kecamatan)))
            ->avg('jumlah') ?? 0);
    }

    private function aggr(string $jenis, ?int $waktuId, ?int $wilayahId, ?string $kecamatan): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
            ->when($waktuId,   fn ($q) => $q->where('waktu_id', $waktuId))
            ->when($wilayahId, fn ($q) => $q->where('wilayah_id', $wilayahId))
            ->when(! $wilayahId && $kecamatan, fn ($q) => $q->whereHas('wilayah', fn ($q) => $q->where('nama_kecamatan', $kecamatan)))
            ->with('kategori')
            ->get()
            ->sortBy('kategori.urutan')
            ->groupBy('kategori.label')
            ->map(fn ($rows) => $rows->sum('jumlah'));
    }
}
