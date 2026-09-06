<?php

namespace Tests\Feature;

use App\Models\DataAgregat;
use App\Models\DimWaktu;
use App\Models\ImportExcel;
use App\Models\KonfigurasiImport;
use App\Models\User;
use App\Services\Import\PembacaSheetDkb;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Uji alur Mode A: unggah berkas DKB → pratinjau → konfirmasi.
 *
 * CATATAN PENTING soal database: proyek ini tidak memakai RefreshDatabase
 * karena pdo_sqlite tidak terpasang, sehingga test berjalan di atas DB
 * pengembangan yang berisi data dummy. Karena itu test ini WAJIB membersihkan
 * datanya sendiri, dan sengaja memakai periode 2098 yang mustahil bentrok
 * dengan data mana pun.
 */
class ImportDkbTest extends TestCase
{
    private const TAHUN    = 2098;
    private const SEMESTER = 2;

    /**
     * Profil khusus test, berisi hanya sheet yang ada di berkas tiruan.
     *
     * Tidak memakai profil standar karena profil itu memetakan 12 sheet,
     * sementara berkas tiruan hanya punya 2 — sisanya akan dilaporkan sebagai
     * galat "sheet tidak ditemukan" dan import diblokir. Itu perilaku yang
     * memang diinginkan untuk berkas sungguhan, jadi yang disesuaikan di sini
     * profilnya, bukan aturan validasinya.
     */
    private const PROFIL = 'Profil Uji Otomatis';

    private string $berkas;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->berkas = $this->buatBerkasDkbTiruan();

        $this->admin = User::where('role', 'petugas')->firstOrFail();

        $this->buatProfilUji();
    }

    protected function tearDown(): void
    {
        $this->bersihkan();
        KonfigurasiImport::where('nama_profil', self::PROFIL)->delete();

        if (is_file($this->berkas)) {
            unlink($this->berkas);
        }

        parent::tearDown();
    }

    private function buatProfilUji(): void
    {
        KonfigurasiImport::where('nama_profil', self::PROFIL)->delete();

        $baris = [
            ['PendudukJK', 'jenis_kelamin', 'Laki-laki', 'Laki-laki', 0],
            ['PendudukJK', 'jenis_kelamin', 'Perempuan', 'Perempuan', 0],
            // offset 2 = grup "Pindah" → sub-kolom "Jumlah"
            ['Pindah_&_Datang', 'mobilitas_pindah', 'Pindah', 'Pindah', 2],
            ['Pindah_&_Datang', 'mobilitas_datang', 'Datang', 'Datang', 2],
        ];

        foreach ($baris as [$sheet, $jenis, $label, $header, $offset]) {
            KonfigurasiImport::create([
                'nama_profil'     => self::PROFIL,
                'nama_sheet'      => $sheet,
                'jenis_indikator' => $jenis,
                'label'           => $label,
                'teks_header'     => $header,
                'offset_kolom'    => $offset,
                'aktif'           => true,
            ]);
        }
    }

    /**
     * Buang data yang dihasilkan test. Sengaja TIDAK menyentuh profil uji:
     * metode ini juga dipanggil di awal tiap test, sementara profilnya baru
     * dibuat di setUp dan masih dibutuhkan sepanjang test berjalan.
     */
    private function bersihkan(): void
    {
        $waktu = DimWaktu::where('tahun', self::TAHUN)->where('semester', self::SEMESTER)->first();

        if ($waktu) {
            DataAgregat::where('waktu_id', $waktu->id)->delete();
            $waktu->delete();
        }

        ImportExcel::where('nama_file', 'DKB_UJI.xlsx')->each(function (ImportExcel $i) {
            if ($i->path_file) {
                Storage::delete($i->path_file);
            }
            $i->delete();
        });
    }

    public function test_alur_unggah_pratinjau_konfirmasi_menyimpan_data(): void
    {
        $this->bersihkan();

        // ── 1. Unggah ────────────────────────────────────────────────────────
        $respon = $this->actingAs($this->admin)->post(route('petugas.import.dkb'), [
            'file'        => new UploadedFile($this->berkas, 'DKB_UJI.xlsx', null, null, true),
            'nama_profil' => self::PROFIL,
        ]);

        $import = ImportExcel::where('nama_file', 'DKB_UJI.xlsx')->latest('id')->firstOrFail();

        $respon->assertRedirect(route('petugas.import.pratinjau', $import));
        $this->assertSame(ImportExcel::STATUS_PRATINJAU, $import->status);
        $this->assertNotNull($import->path_file, 'Berkas harus disimpan agar bisa diparse ulang saat konfirmasi.');

        // Belum ada apa pun yang tersimpan sebelum dikonfirmasi
        $this->assertSame(0, $this->jumlahTersimpan(), 'Pratinjau tidak boleh menulis ke data_agregat.');

        // ── 2. Pratinjau ─────────────────────────────────────────────────────
        $this->actingAs($this->admin)
            ->get(route('petugas.import.pratinjau', $import))
            ->assertOk()
            ->assertSee('Ringkasan per Sheet')
            // Periode terbaca otomatis dari judul sheet
            ->assertSee('value="'.self::TAHUN.'"', false);

        $this->assertSame(0, $this->jumlahTersimpan(), 'Membuka pratinjau tidak boleh menulis apa pun.');

        // ── 3. Konfirmasi ────────────────────────────────────────────────────
        $this->actingAs($this->admin)
            ->post(route('petugas.import.konfirmasi', $import), [
                'tahun'         => self::TAHUN,
                'semester'      => self::SEMESTER,
                'mode_duplikat' => 'timpa',
            ])
            ->assertRedirect(route('petugas.import.index'))
            ->assertSessionHas('success');

        $this->assertSame(ImportExcel::STATUS_BERHASIL, $import->fresh()->status);

        // 15 kelurahan × 4 label (2 sheet × 2 kolom) = 60 baris
        $this->assertSame(60, $this->jumlahTersimpan());

        // Prinsip 3: angka diambil dari tabel pertama (1007), bukan pivot kedua (111)
        $melong = $this->angka('Melong', 'Laki-laki');
        $this->assertNotNull($melong);
        $this->assertSame(1007, $melong->jumlah, 'Importer ikut membaca tabel pivot kedua di bawah tabel utama.');
        $this->assertSame($import->id, $melong->import_id, 'Jejak asal-usul import tidak tercatat.');

        // offset_kolom 2 harus mendarat di sub-kolom "Jumlah" (1001),
        // bukan di sub-kolom "Laki-laki" (11) yang jadi acuan pencarian header
        $this->assertSame(1001, $this->angka('Melong', 'Pindah')?->jumlah,
            'Offset kolom pada header bertingkat mendarat di kolom yang salah.');
        $this->assertSame(2001, $this->angka('Melong', 'Datang')?->jumlah);

        // Prinsip 2: ejaan "KARANG MEKAR" di berkas dikenali lewat alias_wilayah
        $this->assertSame(1056, $this->angka('Karangmekar', 'Laki-laki')?->jumlah,
            'Alias wilayah tidak dipakai saat mencocokkan nama kelurahan.');

        // Baris subtotal kecamatan, total kota, dan persentase tidak boleh ikut tersimpan
        $this->assertSame(15, DataAgregat::whereHas('waktu', fn ($q) => $q->where('tahun', self::TAHUN))
            ->whereHas('kategori', fn ($q) => $q->where('label', 'Laki-laki'))
            ->count(), 'Ada baris non-kelurahan yang ikut terbaca.');
    }

    private function angka(string $kelurahan, string $label): ?DataAgregat
    {
        return DataAgregat::whereHas('wilayah', fn ($q) => $q->where('nama_kelurahan', $kelurahan))
            ->whereHas('waktu', fn ($q) => $q->where('tahun', self::TAHUN))
            ->whereHas('kategori', fn ($q) => $q->where('label', $label))
            ->first();
    }

    public function test_import_yang_dibatalkan_tidak_menyimpan_apa_pun(): void
    {
        $this->bersihkan();

        $this->actingAs($this->admin)->post(route('petugas.import.dkb'), [
            'file'        => new UploadedFile($this->berkas, 'DKB_UJI.xlsx', null, null, true),
            'nama_profil' => self::PROFIL,
        ]);

        $import = ImportExcel::where('nama_file', 'DKB_UJI.xlsx')->latest('id')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('petugas.import.batal', $import))
            ->assertRedirect(route('petugas.import.index'));

        $this->assertSame(ImportExcel::STATUS_DIBATALKAN, $import->fresh()->status);
        $this->assertSame(0, $this->jumlahTersimpan());

        // Konfirmasi setelah dibatalkan harus ditolak, bukan diam-diam menyimpan
        $this->actingAs($this->admin)
            ->post(route('petugas.import.konfirmasi', $import), [
                'tahun' => self::TAHUN, 'semester' => self::SEMESTER, 'mode_duplikat' => 'timpa',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, $this->jumlahTersimpan());
    }

    public function test_admin_dapat_menambah_dan_menghapus_alias_wilayah(): void
    {
        $wilayah = \App\Models\DimWilayah::where('nama_kelurahan', 'Cibeber')->firstOrFail();
        \App\Models\AliasWilayah::where('nama_alias', 'CIBEBER UJI')->delete();

        // Ejaan sengaja berantakan — harus dibakukan saat disimpan
        $this->actingAs($this->admin)
            ->post(route('petugas.wilayah.alias.tambah', $wilayah), ['nama_alias' => '  cibeber   uji  '])
            ->assertSessionHas('success');

        $alias = \App\Models\AliasWilayah::where('nama_alias', 'CIBEBER UJI')->first();
        $this->assertNotNull($alias, 'Alias tidak dibakukan menjadi huruf kapital dengan spasi tunggal.');
        $this->assertSame($wilayah->id, $alias->wilayah_id);

        // Alias yang sama tidak boleh didaftarkan dua kali
        $this->actingAs($this->admin)
            ->post(route('petugas.wilayah.alias.tambah', $wilayah), ['nama_alias' => 'CIBEBER UJI'])
            ->assertSessionHas('error');

        $this->assertSame(1, \App\Models\AliasWilayah::where('nama_alias', 'CIBEBER UJI')->count());

        // Alias baru langsung dipakai importer
        $this->assertSame($wilayah->id, \App\Models\DimWilayah::kamusPencocokan()['CIBEBER UJI'] ?? null);

        $this->actingAs($this->admin)
            ->delete(route('petugas.wilayah.alias.hapus', $alias))
            ->assertSessionHas('success');

        $this->assertSame(0, \App\Models\AliasWilayah::where('nama_alias', 'CIBEBER UJI')->count());
    }

    /**
     * Berkas DKB Semester II 2025 yang asli memuat #REF! di seluruh sheet Agama
     * dan sebagian sheet KTP — tautan rumus antar-berkas yang putus saat berkas
     * dikirim keluar. Sel seperti itu tidak boleh memblokir import (tidak ada
     * angka salah yang bisa masuk) tapi juga tidak boleh diam-diam jadi 0.
     */
    public function test_sel_ref_dilewati_dan_dilaporkan_tanpa_memblokir(): void
    {
        $berkas = $this->buatBerkasDkbTiruan(lakiRusak: true);

        try {
            $hasil = (new PembacaSheetDkb())->baca($berkas, self::PROFIL, null, null, 'DKB_UJI.xlsx');

            $this->assertSame([], $hasil->galat, '#REF! di berkas sumber tidak boleh memblokir import.');

            $peringatan = implode(' ', $hasil->peringatan);
            $this->assertStringContainsString('#REF!', $peringatan);
            $this->assertStringContainsString('Laki-laki', $peringatan);

            $label = array_column($hasil->baris, 'label');
            $this->assertNotContains('Laki-laki', $label, 'Sel #REF! tidak boleh tersimpan sebagai angka.');
            $this->assertContains('Perempuan', $label, 'Kolom sehat di sheet yang sama harus tetap terbaca.');
        } finally {
            unlink($berkas);
        }
    }

    /**
     * Nama berkas dan judul di dalam sheet menyebut tahun yang berbeda.
     *
     * Berkas DKB dirakit manual dan judul satu-dua sheet sering ketinggalan di
     * semester sebelumnya. Yang dipakai adalah suara terbanyak — di sini dua
     * judul sheet (2097) mengalahkan satu nama berkas (2098) — tapi hasilnya
     * ditandai sebagai dugaan supaya Admin memeriksanya lagi.
     */
    public function test_periode_bentrok_memakai_suara_terbanyak(): void
    {
        $berkas = $this->buatBerkasDkbTiruan(self::TAHUN - 1);

        try {
            $hasil = (new PembacaSheetDkb())->baca(
                $berkas,
                self::PROFIL,
                null,
                null,
                'DKB SEMESTER II '.self::TAHUN.'.xlsx',
            );

            $this->assertSame(self::TAHUN - 1, $hasil->tahun,
                'Periode seharusnya diambil dari yang paling banyak disebut (2 judul sheet).');
            $this->assertTrue($hasil->periodeTerdeteksiOtomatis, 'Nilainya harus tetap terisi, bukan dikosongkan.');
            $this->assertTrue($hasil->periodeBentrok, 'Ketidakkonsistenan wajib ditandai.');
            $this->assertStringContainsString('tidak konsisten', implode(' ', $hasil->peringatan));

            // Judul yang seragam: terdeteksi tanpa penanda bentrok
            $seragam = (new PembacaSheetDkb())->baca($berkas, self::PROFIL, null, null, 'DKB_UJI.xlsx');
            $this->assertTrue($seragam->periodeTerdeteksiOtomatis);
            $this->assertFalse($seragam->periodeBentrok);
            $this->assertSame(self::TAHUN - 1, $seragam->tahun);
        } finally {
            unlink($berkas);
        }
    }

    /**
     * Rumus lintas-sheet tetap terbaca walau sheet rujukannya tidak dimuat.
     *
     * Ini mematok perbaikan terpenting importer. Berkas DKB memakai rumus
     * seperti "=Agama_JK!F8", sementara importer hanya memuat sheet yang
     * dipetakan. Dulu Worksheet::toArray() menghitung ulang rumus itu, gagal
     * karena sheet rujukannya tidak ada, lalu mengembalikan '#REF!' — dan
     * seluruh sheet Agama dilaporkan rusak padahal tidak ada satu sel pun
     * yang rusak. Sekarang yang dipakai nilai tersimpan milik selnya sendiri.
     */
    public function test_rumus_lintas_sheet_terbaca_dari_nilai_tersimpan(): void
    {
        $profil = 'Profil Uji Rumus';
        $berkas = $this->buatBerkasRumusLintasSheet();

        KonfigurasiImport::where('nama_profil', $profil)->delete();

        KonfigurasiImport::create([
            'nama_profil'     => $profil,
            'nama_sheet'      => 'Ringkas',
            'jenis_indikator' => 'jenis_kelamin',
            'label'           => 'Laki-laki',
            'teks_header'     => 'Laki-laki',
            'offset_kolom'    => 0,
            'aktif'           => true,
        ]);

        try {
            $hasil = (new PembacaSheetDkb())->baca($berkas, $profil, self::TAHUN, self::SEMESTER);

            $this->assertSame([], $hasil->galat,
                'Rumus yang merujuk sheet tak-dimuat tidak boleh dilaporkan sebagai galat.');

            // Berkas tiruan ini memang hanya punya satu kelurahan, jadi peringatan
            // "1 dari 15" wajar. Yang tidak boleh muncul adalah keluhan #REF!.
            $this->assertStringNotContainsString('#REF!', implode(' ', $hasil->peringatan));

            $nilai = collect($hasil->baris)->firstWhere('nama_kelurahan', 'Melong');
            $this->assertNotNull($nilai, 'Baris hasil rumus tidak terbaca sama sekali.');
            $this->assertSame(1234, $nilai['jumlah'],
                'Nilai tersimpan milik sel rumus tidak dipakai — kemungkinan rumusnya dihitung ulang.');
        } finally {
            KonfigurasiImport::where('nama_profil', $profil)->delete();
            unlink($berkas);
        }
    }

    /** Sheet 'Ringkas' hanya berisi rumus yang menunjuk sheet 'Sumber'. */
    private function buatBerkasRumusLintasSheet(): string
    {
        $sp = new Spreadsheet();
        $sp->removeSheetByIndex(0);

        $sumber = $sp->createSheet();
        $sumber->setTitle('Sumber');
        $sumber->setCellValue('A1', 1234);

        $ringkas = $sp->createSheet();
        $ringkas->setTitle('Ringkas');
        $ringkas->fromArray([
            ['REKAP SEMESTER II TAHUN '.self::TAHUN],
            [],
            ['No', 'Wilayah', 'Laki-laki'],
        ], null, 'A1');
        $ringkas->setCellValue('A4', 1);
        $ringkas->setCellValue('B4', 'MELONG');
        $ringkas->setCellValue('C4', '=Sumber!A1');

        // preCalculateFormulas menuliskan nilai hasil hitung ke dalam berkas —
        // inilah "nilai tersimpan" yang harus dipakai importer.
        $penulis = new Xlsx($sp);
        $penulis->setPreCalculateFormulas(true);

        $path = tempnam(sys_get_temp_dir(), 'dkbF').'.xlsx';
        $penulis->save($path);

        return $path;
    }

    /**
     * Sheet KelompokUmur tersusun terbalik: nama kelurahan berjajar di baris
     * header, kelompok umur menurun ke bawah. Di bawah tabel utamanya ada
     * salinan tabel yang sama berisi angka KUMULATIF — kalau yang terbaca
     * salinan itu, angkanya membengkak tanpa error apa pun.
     */
    public function test_sheet_bersusun_terbalik_terbaca_dari_tabel_pertama(): void
    {
        $profil = 'Profil Uji Terbalik';
        $berkas = $this->buatBerkasTerbalik();

        KonfigurasiImport::where('nama_profil', $profil)->delete();

        foreach ([['0-4 Tahun', '00-04'], ['5-9 Tahun', '05-09']] as [$label, $header]) {
            KonfigurasiImport::create([
                'nama_profil'     => $profil,
                'nama_sheet'      => 'UmurUji',
                'jenis_indikator' => 'kelompok_umur',
                'label'           => $label,
                'teks_header'     => $header,
                'offset_kolom'    => 2,
                'orientasi'       => KonfigurasiImport::ORIENTASI_KOLOM,
                'aktif'           => true,
            ]);
        }

        try {
            $hasil = (new PembacaSheetDkb())->baca($berkas, $profil, self::TAHUN, self::SEMESTER);

            $this->assertSame([], $hasil->galat);

            $nilai = [];
            foreach ($hasil->baris as $b) {
                $nilai["{$b['nama_kelurahan']}|{$b['label']}"] = $b['jumlah'];
            }

            // Kolom TOTAL milik tiap kelurahan (offset 2 dari kolom namanya)
            $this->assertSame(21, $nilai['Melong|0-4 Tahun'] ?? null,
                'Nilai terbalik diambil dari kolom yang salah, atau dari tabel kumulatif di bawahnya.');
            $this->assertSame(25, $nilai['Melong|5-9 Tahun'] ?? null);
            $this->assertSame(61, $nilai['Cibeureum|0-4 Tahun'] ?? null);

            // Baris "JUMLAH" dan blok rekap kecamatan tidak boleh ikut terbaca
            $this->assertCount(4, $hasil->baris, 'Ada kolom non-kelurahan yang ikut terbaca.');
        } finally {
            KonfigurasiImport::where('nama_profil', $profil)->delete();
            unlink($berkas);
        }
    }

    /** Berkas tiruan bersusun terbalik, lengkap dengan jebakan tabel kumulatif. */
    private function buatBerkasTerbalik(): string
    {
        $sp = new Spreadsheet();
        $sp->removeSheetByIndex(0);

        $s = $sp->createSheet();
        $s->setTitle('UmurUji');
        $s->fromArray([
            ['JUMLAH PENDUDUK MENURUT KELOMPOK UMUR SEMESTER II TAHUN '.self::TAHUN],
            [],
            [],
            // Blok rekap kecamatan sengaja ikut ditaruh — harus diabaikan
            ['KELOMPOK UMUR', 'MELONG', null, null, 'CIBEUREUM', null, null, 'CIMAHI SELATAN', null, null],
            [null, 'L', 'P', 'TOTAL', 'L', 'P', 'TOTAL', 'L', 'P', 'TOTAL'],
            ['00-04', 10, 11, 21, 30, 31, 61, 40, 42, 82],
            ['05-09', 12, 13, 25, 32, 33, 65, 44, 46, 90],
            [null, 22, 24, 46, 62, 64, 126, 84, 88, 172],
            [],
            // Salinan kumulatif — angkanya berbeda jauh supaya ketahuan bila terbaca
            ['KELOMPOK UMUR', 'MELONG', null, null, 'CIBEUREUM', null, null],
            [null, 'L', 'P', 'TOTAL', 'L', 'P', 'TOTAL'],
            ['00-04', 999, 999, 1998, 999, 999, 1998],
            ['05-09', 999, 999, 1998, 999, 999, 1998],
        ], null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'dkbT').'.xlsx';
        (new Xlsx($sp))->save($path);

        return $path;
    }

    private function jumlahTersimpan(): int
    {
        return DataAgregat::whereHas('waktu', fn ($q) => $q
            ->where('tahun', self::TAHUN)->where('semester', self::SEMESTER))->count();
    }

    /**
     * Berkas tiruan yang meniru jebakan berkas DKB asli: baris judul di atas
     * header, subtotal kecamatan di tengah, total kota & persentase di bawah,
     * lalu tabel pivot kedua berisi angka berbeda.
     *
     * @param  int   $tahunJudul  tahun yang ditulis di judul sheet — dibuat bisa
     *                            berbeda dari nama berkas untuk menguji periode bentrok
     * @param  bool  $lakiRusak   isi kolom Laki-laki dengan #REF! seperti berkas
     *                            DKB nyata yang tautan rumusnya putus
     */
    private function buatBerkasDkbTiruan(?int $tahunJudul = null, bool $lakiRusak = false): string
    {
        $tahunJudul ??= self::TAHUN;

        $kelurahan = [
            ['MELONG', 'Cimahi Selatan'], ['CIBEUREUM', 'Cimahi Selatan'], ['UTAMA', 'Cimahi Selatan'],
            ['LEUWIGAJAH', 'Cimahi Selatan'], ['CIBEBER', 'Cimahi Selatan'],
            ['BAROS', 'Cimahi Tengah'], ['CIGUGUR TGH', 'Cimahi Tengah'], ['KARANG MEKAR', 'Cimahi Tengah'],
            ['SETIAMANAH', 'Cimahi Tengah'], ['PADASUKA', 'Cimahi Tengah'], ['CIMAHI', 'Cimahi Tengah'],
            ['PASIRKALIKI', 'Cimahi Utara'], ['CIBABAT', 'Cimahi Utara'], ['CITEUREUP', 'Cimahi Utara'],
            ['CIPAGERAN', 'Cimahi Utara'],
        ];

        $sp = new Spreadsheet();
        $sp->removeSheetByIndex(0);

        $s = $sp->createSheet();
        $s->setTitle('PendudukJK');
        $s->fromArray([
            ['DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL KOTA CIMAHI'],
            ['DATA AGREGAT KEPENDUDUKAN SEMESTER II TAHUN '.$tahunJudul],
            [],
            ['No', 'Wilayah', 'Kecamatan', 'Laki-laki', 'Perempuan', 'Jumlah'],
        ], null, 'A1');

        $r = 5;
        $n = 1;
        $sub = 0;
        $kecSebelum = null;

        foreach ($kelurahan as [$nama, $kec]) {
            if ($kecSebelum !== null && $kec !== $kecSebelum) {
                $s->fromArray([['', 'JUMLAH '.strtoupper($kecSebelum), '', $sub, $sub, $sub * 2]], null, "A{$r}");
                $r++;
                $sub = 0;
            }
            $l = 1000 + $n * 7;
            $p = 1000 + $n * 5;
            $s->fromArray([[$n, $nama, $kec, $lakiRusak ? '#REF!' : $l, $p, $l + $p]], null, "A{$r}");
            $sub += $l;
            $kecSebelum = $kec;
            $r++;
            $n++;
        }

        $s->fromArray([['', 'JUMLAH '.strtoupper($kecSebelum), '', $sub, $sub, $sub * 2]], null, "A{$r}");
        $r++;
        $s->fromArray([['', 'TOTAL KOTA CIMAHI', '', 99999, 99999, 999999]], null, "A{$r}");
        $r++;
        $s->fromArray([['', 'PERSENTASE', '', '50,1%', '49,9%', '100%']], null, "A{$r}");
        $r += 3;

        // Tabel pivot kedua — angkanya sengaja beda supaya ketahuan kalau terbaca
        $s->fromArray([['REKAP ULANG'], ['Wilayah', 'Laki-laki', 'Perempuan']], null, "A{$r}");
        $r += 2;
        foreach ($kelurahan as [$nama]) {
            $s->fromArray([[$nama, 111, 222]], null, "A{$r}");
            $r++;
        }

        // Sheet berheader bertingkat, untuk menguji offset_kolom
        $s = $sp->createSheet();
        $s->setTitle('Pindah_&_Datang');
        $s->fromArray([
            ['LAPORAN MOBILITAS PENDUDUK SEMESTER II TAHUN '.$tahunJudul],
            [],
            ['No', 'Wilayah', 'Pindah', null, null, 'Datang', null, null],
            [null, null, 'Laki-laki', 'Perempuan', 'Jumlah', 'Laki-laki', 'Perempuan', 'Jumlah'],
        ], null, 'A1');

        $r = 5;
        $n = 1;
        foreach ($kelurahan as [$nama]) {
            $s->fromArray([[$n, $nama, 10 + $n, 20 + $n, null, 30 + $n, 40 + $n, null]], null, "A{$r}");
            // Ditulis eksplisit sebagai teks: fromArray() akan mengubah '1.001' jadi float 1.001
            $s->setCellValueExplicit("E{$r}", '1.'.str_pad((string) $n, 3, '0', STR_PAD_LEFT), DataType::TYPE_STRING);
            $s->setCellValueExplicit("H{$r}", '2.'.str_pad((string) $n, 3, '0', STR_PAD_LEFT), DataType::TYPE_STRING);
            $r++;
            $n++;
        }

        $path = tempnam(sys_get_temp_dir(), 'dkb').'.xlsx';
        (new Xlsx($sp))->save($path);

        return $path;
    }
}
