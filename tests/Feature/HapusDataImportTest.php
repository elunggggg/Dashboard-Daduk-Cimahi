<?php

namespace Tests\Feature;

use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use App\Models\ImportExcel;
use App\Models\User;
use Tests\TestCase;

/**
 * Admin salah unggah berkas → datanya harus bisa dibuang lagi.
 *
 * Memakai periode 2097 yang mustahil bentrok dengan data sungguhan, dan
 * membersihkan miliknya sendiri (proyek ini tidak bisa memakai RefreshDatabase
 * karena pdo_sqlite tidak terpasang).
 */
class HapusDataImportTest extends TestCase
{
    private const TAHUN = 2097;

    public function test_admin_dapat_menghapus_data_hasil_import(): void
    {
        $admin    = User::where('role', 'petugas')->firstOrFail();
        $wilayah  = DimWilayah::firstOrFail();
        $kategori = DimKategori::firstOrFail();

        $this->bersihkan();

        $waktu = DimWaktu::create(['tahun' => self::TAHUN, 'semester' => 1, 'label' => 'S1 '.self::TAHUN]);

        $import = ImportExcel::create([
            'user_id'      => $admin->id,
            'nama_file'    => 'UJI_HAPUS.xlsx',
            'mode'         => ImportExcel::MODE_DKB,
            'status'       => ImportExcel::STATUS_BERHASIL,
            'tahun'        => self::TAHUN,
            'semester'     => 1,
            'jumlah_baris' => 1,
        ]);

        DataAgregat::create([
            'wilayah_id'  => $wilayah->id,
            'waktu_id'    => $waktu->id,
            'kategori_id' => $kategori->id,
            'jumlah'      => 123,
            'import_id'   => $import->id,
        ]);

        $this->assertTrue($import->fresh()->bisaHapusData());

        $this->actingAs($admin)
            ->delete(route('petugas.import.hapus-data', $import))
            ->assertRedirect(route('petugas.import.index'))
            ->assertSessionHas('success');

        $import->refresh();

        $this->assertSame(0, DataAgregat::where('import_id', $import->id)->count(),
            'Baris data_agregat milik import ini seharusnya sudah terhapus.');
        $this->assertNotNull($import->data_dihapus_pada, 'Jejak penghapusan tidak dicatat.');
        $this->assertSame($admin->id, $import->dihapus_oleh);
        $this->assertSame(1, $import->jumlah_baris_dihapus);

        // Catatan riwayatnya TETAP ada — itu bagian dari jejak audit
        $this->assertSame(ImportExcel::STATUS_BERHASIL, $import->status);

        // Periode yang jadi kosong ikut dibuang supaya tidak muncul di dropdown
        $this->assertNull(DimWaktu::find($waktu->id),
            'Periode tanpa data seharusnya ikut dibuang.');

        // Tidak bisa dihapus dua kali
        $this->assertFalse($import->bisaHapusData());
        $this->actingAs($admin)
            ->delete(route('petugas.import.hapus-data', $import))
            ->assertSessionHas('error');

        $this->bersihkan();
    }

    private function bersihkan(): void
    {
        foreach (DimWaktu::where('tahun', self::TAHUN)->get() as $w) {
            DataAgregat::where('waktu_id', $w->id)->delete();
            $w->delete();
        }

        ImportExcel::where('nama_file', 'UJI_HAPUS.xlsx')->delete();
    }
}
