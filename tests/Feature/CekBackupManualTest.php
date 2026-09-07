<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Backup manual benar-benar menjalankan mysqldump dan menghasilkan arsip .zip
 * nyata (bukan sekadar redirect "sukses" tanpa isi) — dan riwayatnya tersimpan
 * dengan status & ukuran yang benar (bug lama: keduanya selalu kosong karena
 * mismatch $fillable, lihat migrasi add_status_catatan_to_backups_table).
 *
 * CATATAN: test ini betul-betul memanggil `backup:run` terhadap database
 * pengembangan (bukan mock) — disengaja, supaya kegagalan mysqldump/konfigurasi
 * asli ketahuan di sini. Arsip & baris yang dibuat dibersihkan di tearDown()
 * supaya aman dijalankan berulang tanpa menumpuk sampah di storage backup.
 */
class CekBackupManualTest extends TestCase
{
    private ?string $namaFileDibuat = null;

    public function test_backup_manual_menghasilkan_arsip(): void
    {
        $petugas = User::where('role', 'petugas')->firstOrFail();

        $sebelum = Backup::count();

        $res = $this->actingAs($petugas)->post(route('petugas.backup.store'));

        $res->assertRedirect();
        $sesi = session()->all();
        fwrite(STDERR, "\n  session flash: ".json_encode(array_intersect_key($sesi, array_flip(['success', 'error']))));

        $this->assertGreaterThan($sebelum, Backup::count(), 'Tidak ada catatan Backup baru dibuat.');

        $terakhir = Backup::latest('id')->first();
        $this->namaFileDibuat = $terakhir->nama_file;

        fwrite(STDERR, "\n  backup terakhir: status={$terakhir->status} file={$terakhir->nama_file} ukuran=".number_format($terakhir->ukuran_bytes ?? 0));

        $this->assertSame('sukses', $terakhir->status, 'Backup gagal — cek log/mysqldump.');
        $this->assertGreaterThan(0, $terakhir->ukuran_bytes ?? 0, 'Arsip backup kosong (0 byte).');
    }

    protected function tearDown(): void
    {
        // Buang arsip .zip nyata + baris riwayat yang dibuat test ini, supaya
        // test bisa diulang tanpa menumpuk sampah di storage backup asli.
        if ($this->namaFileDibuat) {
            $disk   = Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
            $folder = config('backup.backup.name', config('app.name'));
            $disk->delete($folder.'/'.$this->namaFileDibuat);

            Backup::where('nama_file', $this->namaFileDibuat)->delete();
        }

        parent::tearDown();
    }
}
