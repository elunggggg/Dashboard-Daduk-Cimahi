<?php

namespace App\Http\Controllers;

use App\Models\DataAgregat;
use App\Services\FilterWilayahService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SosialController extends Controller
{
    public function __construct(private FilterWilayahService $filter) {}

    public function __invoke(Request $request): View
    {
        $latestWaktu   = $this->filter->getLatestWaktu();
        $waktuList     = $this->filter->getWaktuList();
        $kecamatanList = $this->filter->getKecamatanList();
        $wilayahList   = $this->filter->getWilayahList();

        $waktuId   = $this->filter->periodeTerpilih($request);
        $wilayahId = $request->integer('wilayah_id') ?: null;
        $kecamatan = $request->string('kecamatan')->toString() ?: null;

        // Diurutkan dari terbanyak — sesuai spek tampilan (bar chart Agama/
        // Pekerjaan/Pendidikan harus descending), bukan urutan seeder.
        $pendidikanData = $this->aggr('pendidikan',     $waktuId, $wilayahId, $kecamatan)->sortDesc();
        $pekerjaanData  = $this->aggr('pekerjaan',      $waktuId, $wilayahId, $kecamatan)->sortDesc();

        // Rincian jenis pekerjaan (17 jenis, sheet STATUS_PEKERJAAN v1) —
        // melengkapi $pekerjaanData yang cuma 11 KELOMPOK besar.
        $jenisPekerjaanData = $this->aggr('jenis_pekerjaan', $waktuId, $wilayahId, $kecamatan);

        // Penduduk usia sekolah per jenjang (sheet UsiaSekolah).
        $usiaSekolahData = $this->aggr('usia_sekolah', $waktuId, $wilayahId, $kecamatan);
        $agamaData      = $this->aggr('agama',          $waktuId, $wilayahId, $kecamatan)->sortDesc();
        $ktpData        = $this->aggr('kepemilikan_ktp',$waktuId, $wilayahId, $kecamatan);
        $kkData         = $this->aggr('kepemilikan_kk', $waktuId, $wilayahId, $kecamatan);
        $kiaData        = $this->aggr('kepemilikan_kia',$waktuId, $wilayahId, $kecamatan);
        $aktaLahirData  = $this->aggr('akta_lahir',    $waktuId, $wilayahId, $kecamatan);
        $aktaKawinData  = $this->aggr('akta_kawin',    $waktuId, $wilayahId, $kecamatan);
        $golonganDarahData = collect(['A', 'B', 'AB', 'O', 'Tidak Tahu'])
            ->mapWithKeys(fn ($label) => [$label => $this->aggr('golongan_darah', $waktuId, $wilayahId, $kecamatan)->get($label, 0)]);

        // Subset status KTP/KK dalam urutan tetap (bukan sortDesc) — "Wajib
        // KTP"/"Jumlah Kepala Keluarga" adalah PENYEBUT (dipakai $pctKtp/$pctKK
        // di bawah), bukan status, jadi sengaja dikeluarkan dari bar ini.
        $ktpStatusData = collect(['Sudah Rekam KTP', 'Belum Rekam KTP', 'Sudah Cetak KTP', 'Belum Cetak KTP'])
            ->mapWithKeys(fn ($label) => [$label => $ktpData->get($label, 0)]);
        $kkStatusData = collect(['KK Sudah TTE', 'KK Belum TTE'])
            ->mapWithKeys(fn ($label) => [$label => $kkData->get($label, 0)]);

        /*
         * Stacked bar per kelurahan (Memiliki vs Belum) — SELALU seluruh 15
         * kelurahan terlepas dari filter kelurahan/kecamatan aktif (begitu
         * memang gunanya: membandingkan antar kelurahan), tapi tetap ikut
         * filter periode. Pola sama dengan perKecamatan() di bawah, hanya
         * granularitasnya kelurahan.
         */
        $aktaLahirKelurahanData = $this->stackedPerKelurahan('akta_lahir', [
            'Memiliki Akta Lahir' => 'memiliki', 'Belum Memiliki Akta Lahir' => 'belum',
        ], $waktuId);
        $kiaKelurahanData = $this->stackedPerKelurahan('kepemilikan_kia', [
            'Memiliki KIA' => 'memiliki', 'Belum Memiliki KIA' => 'belum',
        ], $waktuId);
        $ktpKelurahanData = $this->stackedPerKelurahan('kepemilikan_ktp', [
            'Sudah Rekam KTP' => 'memiliki', 'Belum Rekam KTP' => 'belum',
        ], $waktuId);

        // Rincian jenis kelamin kepala keluarga — melengkapi $kkData yang
        // hanya punya total. Sengaja jenis_indikator terpisah (bukan
        // ditambahkan ke kepemilikan_kk) karena sumbernya sheet lain
        // (Jumlah_KK, bukan KK) dan maknanya beda: ini rincian ORANGnya,
        // bukan status TTE kartu keluarganya.
        $kepalaKeluargaJkData = $this->aggr('kepala_keluarga_jk', $waktuId, $wilayahId, $kecamatan);

        // Akta lahir per kelompok usia — melengkapi $aktaLahirData (semua usia).
        $aktaLahir05Data  = $this->aggr('akta_lahir_0_5',  $waktuId, $wilayahId, $kecamatan);
        $aktaLahir017Data = $this->aggr('akta_lahir_0_17', $waktuId, $wilayahId, $kecamatan);

        // Status hubungan dalam keluarga (sheet SHBKEL) — urutan tetap sesuai
        // hierarki keluarga (bukan sortDesc), sama seperti daftar kolom di
        // KonfigurasiImportSeeder::$profil['SHBKEL'].
        $shbkelDataRaw = $this->aggr('status_hubungan_keluarga', $waktuId, $wilayahId, $kecamatan);
        $shbkelData = collect([
            'Kepala Keluarga', 'Suami', 'Isteri', 'Anak', 'Menantu', 'Cucu',
            'Orang Tua', 'Mertua', 'Famili Lain', 'Pembantu', 'Lainnya',
        ])->mapWithKeys(fn ($label) => [$label => $shbkelDataRaw->get($label, 0)]);

        // Angkatan kerja & TPAK. TPAK di sini SENGAJA dihitung ulang dari dua
        // komponen mentahnya (bukan memakai label 'TPAK (%)' hasil impor apa
        // adanya) — nilai impor per kelurahan per kelompok umur tidak sah
        // dijumlahkan begitu saja ketika filter kelurahan/kecamatan berubah,
        // sedangkan rasio dari dua komponen yang sudah dijumlahkan benar
        // untuk kombinasi filter manapun. Pola sama dengan $pctKtp dkk di bawah.
        $angkatanKerjaData = $this->aggr('angkatan_kerja', $waktuId, $wilayahId, $kecamatan);
        $pctTpak = $this->pctMiliki(
            $angkatanKerjaData,
            'Angkatan Kerja',
            $angkatanKerjaData->get('Jumlah Penduduk Usia Kerja', 0),
        );

        /*
         * Angkatan kerja per tingkat pendidikan (Tabel 38) — se-Kota, sama
         * seperti $terbitTahunan di bawah (sheet sumbernya memang rekap kota,
         * bukan per kelurahan). Dirangkai jadi satu baris per jenjang supaya
         * gampang ditampilkan sebagai tabel.
         */
        $akPendidikanJumlah   = $this->aggr('ak_pendidikan_jumlah_penduduk', $waktuId, null, null);
        $akPendidikanAngkatan = $this->aggr('ak_pendidikan_angkatan_kerja', $waktuId, null, null);
        $akPendidikanBekerja  = $this->aggr('ak_pendidikan_bekerja', $waktuId, null, null);
        $akPendidikanData = collect([
            'TIDAK/BLM SEKOLAH', 'BELUM TAMAT SD/SEDERAJAT', 'TAMAT SD/SEDERAJAT',
            'SLTP/SEDERAJAT', 'SLTA/SEDERAJAT', 'DIPLOMA I/II',
            'AKADEMI/DIPLOMA III/S. MUDA', 'DIPLOMA IV/STRATA I', 'STRATA-II', 'STRATA-III',
        ])->map(function ($jenjang) use ($akPendidikanJumlah, $akPendidikanAngkatan, $akPendidikanBekerja) {
            $jumlah   = $akPendidikanJumlah->get("Jumlah Penduduk ({$jenjang})", 0);
            $angkatan = $akPendidikanAngkatan->get("Angkatan Kerja ({$jenjang})", 0);
            $bekerja  = $akPendidikanBekerja->get("Bekerja ({$jenjang})", 0);

            return [
                'jenjang'  => $jenjang,
                'jumlah'   => $jumlah,
                'angkatan' => $angkatan,
                'bekerja'  => $bekerja,
                'apak'     => $jumlah > 0 ? round($angkatan / $jumlah * 100, 1) : 0,
            ];
        });

        /*
         * Kepala Keluarga (KK) — Tabel 26-29, melengkapi $kkData (yang cuma
         * status TTE kartu keluarga) dengan rincian demografi para KEPALA
         * KELUARGA itu sendiri.
         *
         * kk_pendidikan disimpan sebagai label "... (L)"/"... (P)" terpisah
         * (lihat catatan panjang di KonfigurasiImportSeeder::$kkPendidikan —
         * kolom "Total" bawaan sheetnya terbukti salah untuk 4 kelurahan),
         * jadi totalnya dijumlahkan ulang di sini.
         */
        $kkStatusKawinData = $this->aggr('kk_status_kawin', $waktuId, $wilayahId, $kecamatan);
        $kkKelPekerjaanData = $this->aggr('kk_kelompok_pekerjaan', $waktuId, $wilayahId, $kecamatan);
        $kkAgamaData = $this->aggr('kk_agama', $waktuId, $wilayahId, $kecamatan);
        $kkPendidikanRaw = $this->aggr('kk_pendidikan', $waktuId, $wilayahId, $kecamatan);
        $kkPendidikanData = collect([
            'Tidak/Belum Sekolah', 'Belum Tamat SD/Sederajat', 'Tamat SD/Sederajat',
            'SLTP/Sederajat', 'SLTA/Sederajat', 'Diploma I/II',
            'Akademi/Diploma III', 'Diploma IV/Strata I', 'Strata II', 'Strata III',
        ])->mapWithKeys(fn ($level) => [
            $level => $kkPendidikanRaw->get("{$level} (L)", 0) + $kkPendidikanRaw->get("{$level} (P)", 0),
        ]);

        // Agama menurut kelompok umur PER KECAMATAN (Tabel 33) — melengkapi $agamaData (per kelurahan, tanpa umur).
        $agamaKuData = $this->perKecamatan([
            'agama_ku_islam' => 'Islam', 'agama_ku_kristen' => 'Kristen', 'agama_ku_katholik' => 'Katolik',
            'agama_ku_hindu' => 'Hindu', 'agama_ku_budha' => 'Buddha', 'agama_ku_khonghucu' => 'Konghucu',
            'agama_ku_kepercayaan' => 'Kepercayaan',
        ], $waktuId);

        // Status perkawinan Kepala Keluarga menurut kelompok umur PER KECAMATAN (Tabel 29).
        $kkKawinKuData = $this->perKecamatan([
            'kk_kawin_ku_belum_kawin' => 'Belum Kawin', 'kk_kawin_ku_kawin' => 'Kawin',
            'kk_kawin_ku_cerai_hidup' => 'Cerai Hidup', 'kk_kawin_ku_cerai_mati' => 'Cerai Mati',
        ], $waktuId);

        // Kepala Keluarga (KK) menurut jenis pekerjaan KTP-EL, penuh 99 jenis (Tabel 27), se-Kota.
        $kkPekerjaanData = $this->aggr('kk_pekerjaan', $waktuId, null, null)
            ->sortDesc();

        $totalPenduduk = $this->aggr('jenis_kelamin', $waktuId, $wilayahId, $kecamatan)->sum();

        /*
         * Persen kepemilikan dokumen — penyebutnya diambil dari kolom "Jumlah …"
         * milik berkas DKB itu sendiri, bukan dari total penduduk.
         *
         * Membagi semuanya dengan total penduduk (cara lama) menghasilkan angka
         * yang salah arti: KIA hanya berlaku untuk anak 0–17 tahun, akta kawin
         * hanya untuk penduduk berstatus kawin, dan KTP hanya untuk yang sudah
         * wajib KTP. Disdukcapil sudah menyediakan penyebut resminya di kolom
         * sebelah, jadi angkanya kini sebanding dengan laporan mereka.
         */
        $pctKtp      = $this->pctMiliki($ktpData,       'Sudah Cetak KTP',     $ktpData->get('Wajib KTP', 0));
        $pctKK       = $this->pctMiliki($kkData,        'KK Sudah TTE',        $kkData->get('Jumlah Kepala Keluarga', 0));
        $pctKia      = $this->pctMiliki($kiaData,       'Memiliki KIA',        $kiaData->get('Jumlah Anak Usia 0-17 Tahun', 0));
        $pctAktaLahir= $this->pctMiliki($aktaLahirData, 'Memiliki Akta Lahir', $aktaLahirData->get('Jumlah Penduduk', 0));
        $pctAktaKawin= $this->pctMiliki($aktaKawinData, 'Memiliki Akta Kawin', $aktaKawinData->get('Penduduk Status Kawin', 0));
        $pctAktaLahir05  = $this->pctMiliki($aktaLahir05Data,  'Memiliki Akta Lahir 0-5 Tahun',  $aktaLahir05Data->get('Jumlah Anak Usia 0-5 Tahun', 0));
        $pctAktaLahir017 = $this->pctMiliki($aktaLahir017Data, 'Memiliki Akta Lahir 0-17 Tahun', $aktaLahir017Data->get('Jumlah Penduduk Usia 0-17 Tahun', 0));

        /*
         * Penerbitan dokumen (per bulan, se-Kota) — sengaja TIDAK ikut
         * filter wilayah/kecamatan: sheet sumbernya (TerbitAktaKawin dkk)
         * memang rekap kota, bukan per kelurahan (lihat ORIENTASI_KOTA).
         * Yang ditampilkan cuma total setahun per jenis dokumen — rincian
         * 12 bulannya ada di Ekspor PDF Buku Profil bagi yang butuh detail.
         */
        $terbitAktaKawin  = $this->aggr('terbit_akta_kawin', $waktuId, null, null);
        $terbitAktaCerai  = $this->aggr('terbit_akta_cerai', $waktuId, null, null);
        $terbitPengakuan  = $this->aggr('terbit_pengakuan_anak', $waktuId, null, null);
        $terbitPengesahan = $this->aggr('terbit_pengesahan_anak', $waktuId, null, null);

        $totalTerbit = fn (Collection $d, string $sub) => $d
            ->filter(fn ($v, $k) => str_contains($k, $sub))
            ->sum();

        $terbitTahunan = [
            'Akta Perkawinan'       => $totalTerbit($terbitAktaKawin, 'Diterbitkan (TTE)'),
            'Akta Perceraian'       => $totalTerbit($terbitAktaCerai, 'Diterbitkan (TTE)'),
            'Akta Pengakuan Anak'   => $totalTerbit($terbitPengakuan, 'Diterbitkan (TTE)'),
            'Akta Pengesahan Anak'  => $totalTerbit($terbitPengesahan, 'Diterbitkan (TTE)'),
        ];

        $selectedWaktu = $waktuList->firstWhere('id', $waktuId);

        return view('sosial.index', compact(
            'pendidikanData', 'pekerjaanData', 'jenisPekerjaanData', 'usiaSekolahData', 'agamaData',
            'ktpData', 'kkData', 'kiaData', 'aktaLahirData', 'aktaKawinData', 'golonganDarahData',
            'ktpStatusData', 'kkStatusData',
            'aktaLahirKelurahanData', 'kiaKelurahanData', 'ktpKelurahanData',
            'kepalaKeluargaJkData', 'aktaLahir05Data', 'aktaLahir017Data',
            'shbkelData', 'angkatanKerjaData', 'pctTpak', 'akPendidikanData',
            'kkStatusKawinData', 'kkKelPekerjaanData', 'kkAgamaData', 'kkPendidikanData',
            'agamaKuData', 'kkKawinKuData', 'kkPekerjaanData',
            'pctKtp', 'pctKK', 'pctKia', 'pctAktaLahir', 'pctAktaKawin',
            'pctAktaLahir05', 'pctAktaLahir017', 'terbitTahunan',
            'totalPenduduk', 'waktuList', 'kecamatanList', 'wilayahList',
            'waktuId', 'wilayahId', 'kecamatan',
            'selectedWaktu', 'latestWaktu',
        ));
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

    /**
     * Total per kecamatan untuk sekumpulan jenis_indikator ORIENTASI_KECAMATAN
     * (Agama_KelUmur_Kec, KK_Kawin_KelUmur, dst) — hasilnya
     * kecamatan => [label kategori => total].
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

    /**
     * Dua label (mis. Memiliki/Belum) untuk SELURUH kelurahan — dipakai
     * stacked bar per kelurahan. Selalu 15 kelurahan terlepas dari filter
     * kelurahan/kecamatan aktif (begitu memang gunanya: perbandingan antar
     * kelurahan), tapi tetap ikut filter periode.
     *
     * @param  array<string, string>  $labelKeKunci  label DB => kunci tampilan ('memiliki'/'belum')
     */
    private function stackedPerKelurahan(string $jenis, array $labelKeKunci, ?int $waktuId): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->whereIn('label', array_keys($labelKeKunci))->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->with(['kategori', 'wilayah'])
            ->get()
            ->groupBy(fn ($row) => $row->wilayah->nama_kelurahan)
            ->map(fn ($rows) => $rows
                ->groupBy('kategori.label')
                ->mapWithKeys(fn ($g, $label) => [$labelKeKunci[$label] => $g->sum('jumlah')]))
            ->sortKeys();
    }

    /** $penyebut = populasi yang memang wajib punya dokumen itu, bukan seluruh penduduk. */
    private function pctMiliki(Collection $data, string $key, int $penyebut): float
    {
        if ($penyebut <= 0) {
            return 0;
        }

        return round($data->get($key, 0) / $penyebut * 100, 1);
    }
}
