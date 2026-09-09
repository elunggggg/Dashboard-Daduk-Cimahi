<?php

namespace Tests\Feature;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use App\Models\User;
use App\Services\FilterWilayahService;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Filter periode punya TIGA keadaan yang di URL sama-sama tampak kosong:
 * belum memilih (→ periode terbaru), memilih satu periode, dan memilih
 * "Semua Periode" (→ null). Yang membedakan dua yang terakhir adalah ada
 * tidaknya parameter waktu_id, bukan nilainya.
 */
class FilterPeriodeTest extends TestCase
{
    private const TAHUN = 2096;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bersihkan();
    }

    protected function tearDown(): void
    {
        $this->bersihkan();
        parent::tearDown();
    }

    public function test_tiga_keadaan_pemilihan_periode(): void
    {
        [$lama, $baru] = $this->siapkanDuaPeriode();
        $filter        = app(FilterWilayahService::class);

        $this->assertSame(
            $baru->id,
            $filter->periodeTerpilih(Request::create('/mobilitas')),
            'Kunjungan pertama harus jatuh ke periode terbaru.'
        );

        $this->assertSame(
            $lama->id,
            $filter->periodeTerpilih(Request::create('/mobilitas?waktu_id=' . $lama->id)),
            'Pilihan periode tertentu harus dihormati.'
        );

        $this->assertNull(
            $filter->periodeTerpilih(Request::create('/mobilitas?waktu_id='), bolehSemua: true),
            '"Semua Periode" harus menghasilkan null pada halaman yang membolehkannya.'
        );
    }

    /**
     * Angka stok tidak boleh digabung antar semester — orang yang sama akan
     * terhitung sekali di tiap periode. URL yang memaksa kosong harus jatuh
     * kembali ke periode terbaru, bukan menghasilkan angka tanpa arti.
     */
    public function test_halaman_stok_menolak_gabungan_periode(): void
    {
        [$lama, $baru] = $this->siapkanDuaPeriode();
        $filter        = app(FilterWilayahService::class);

        $this->assertSame(
            $baru->id,
            $filter->periodeTerpilih(Request::create('/demografi?waktu_id=')),
            'Tanpa bolehSemua, periode kosong harus jatuh ke periode terbaru.'
        );

        // 200 = angka periode terbaru saja; 300 = gabungan dua periode
        $this->get('/demografi?waktu_id=')
            ->assertOk()
            ->assertSee('200', false)
            ->assertDontSee('Semua Periode');

        $this->get('/sosial')->assertOk()->assertDontSee('Semua Periode');
    }

    /**
     * Sejak Mobilitas ikut mesin grid terpadu (2026-09-09, bisa saling pindah
     * bagian dengan Dashboard/Demografi/Sosial), ia memakai filter periode
     * STANDAR — tidak ada lagi opsi "Semua Periode"/gabungan. Kunjungan biasa
     * jatuh ke periode terbaru, sama seperti modul lain.
     */
    public function test_mobilitas_pakai_filter_periode_standar(): void
    {
        [$lama, $baru] = $this->siapkanDuaPeriode();

        $this->get('/mobilitas')
            ->assertOk()
            ->assertViewIs('mobilitas.index')
            ->assertDontSee('Semua Periode');
    }

    /**
     * Dashboard Petugas (2026-08-25) bukan lagi KPI+Perbandingan — sekarang
     * halaman menu/hub ke fitur Halaman Petugas (lihat DashboardController).
     * KPI+Perbandingan sekarang hanya ada di Dashboard Publik (/).
     */
    public function test_dashboard_admin_menampilkan_menu_fitur_petugas(): void
    {
        $admin = User::where('role', 'petugas')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Import Data')
            ->assertSee('Kelola Wilayah');
    }

    public function test_dashboard_publik_menampilkan_periode_di_box_perbandingan(): void
    {
        [$lama, $baru] = $this->siapkanDuaPeriode();

        $this->get('/')
            ->assertOk()
            ->assertSee($baru->label)              // opsi dropdown Perbandingan
            ->assertSee($lama->label);
    }

    /** @return array{0: DimWaktu, 1: DimWaktu} */
    private function siapkanDuaPeriode(): array
    {
        $wilayah  = DimWilayah::firstOrFail();
        $kategori = DimKategori::where('jenis_indikator', 'jenis_kelamin')->firstOrFail();

        $lama = DimWaktu::create(['tahun' => self::TAHUN, 'semester' => 1, 'label' => 'S1 ' . self::TAHUN]);
        $baru = DimWaktu::create(['tahun' => self::TAHUN, 'semester' => 2, 'label' => 'S2 ' . self::TAHUN]);

        foreach ([[$lama, 100], [$baru, 200]] as [$waktu, $jumlah]) {
            DataAgregat::create([
                'wilayah_id'  => $wilayah->id,
                'waktu_id'    => $waktu->id,
                'kategori_id' => $kategori->id,
                'jumlah'      => $jumlah,
            ]);
        }

        return [$lama, $baru];
    }

    private function bersihkan(): void
    {
        foreach (DimWaktu::where('tahun', self::TAHUN)->get() as $w) {
            DataAgregat::where('waktu_id', $w->id)->delete();
            $w->delete();
        }
    }
}
