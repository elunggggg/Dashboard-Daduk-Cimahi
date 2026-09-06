<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    /** Disk & folder tempat spatie/laravel-backup menaruh arsipnya. */
    private function disk()
    {
        return Storage::disk(config('backup.backup.destination.disks')[0] ?? 'local');
    }

    private function folder(): string
    {
        return config('backup.backup.name', config('app.name'));
    }

    public function index(): View
    {
        $disk  = $this->disk();
        $files = collect($disk->files($this->folder()))
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.zip'))
            ->map(fn ($f) => [
                'path'   => $f,
                'nama'   => basename($f),
                'ukuran' => $disk->size($f),
                'waktu'  => $disk->lastModified($f),
            ])
            ->sortByDesc('waktu')
            ->values();

        return view('petugas.backup.index', [
            'files'    => $files,
            'riwayat'  => Backup::latest('id')->take(10)->get(),
            'totalByte' => $files->sum('ukuran'),
        ]);
    }

    public function store(): RedirectResponse
    {
        // Sinkron — memblokir request sampai selesai. Untuk basis data sebesar ini
        // masih wajar; kalau nanti membengkak, pindahkan ke queue.
        @set_time_limit(300);

        try {
            $kode = Artisan::call('backup:run', ['--only-db' => true]);
            $out  = trim(Artisan::output());
        } catch (Throwable $e) {
            Backup::create([
                'nama_file' => '—',
                'status'    => 'gagal',
                'catatan'   => substr($e->getMessage(), 0, 1000),
            ]);

            $this->audit->record(AuditLogService::AKSI_BACKUP, 'backups', null, ['status' => 'gagal']);

            return back()->with('error', 'Backup gagal: '.$e->getMessage());
        }

        if ($kode !== 0) {
            Backup::create([
                'nama_file' => '—',
                'status'    => 'gagal',
                'catatan'   => substr($out, -1000),
            ]);

            $this->audit->record(AuditLogService::AKSI_BACKUP, 'backups', null, ['status' => 'gagal']);

            return back()->with('error', 'Backup gagal. Periksa apakah mysqldump tersedia (DB_DUMP_BINARY_PATH di .env).');
        }

        // Ambil arsip terbaru sebagai catatan
        $disk    = $this->disk();
        $terbaru = collect($disk->files($this->folder()))
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.zip'))
            ->sortByDesc(fn ($f) => $disk->lastModified($f))
            ->first();

        $catatan = Backup::create([
            'nama_file'   => $terbaru ? basename($terbaru) : '—',
            'ukuran_byte' => $terbaru ? $disk->size($terbaru) : 0,
            'status'      => 'sukses',
        ]);

        $this->audit->record(AuditLogService::AKSI_BACKUP, 'backups', null, [
            'nama_file'   => $catatan->nama_file,
            'ukuran_byte' => $catatan->ukuran_byte,
        ]);

        return back()->with('success', "Backup berhasil dibuat: {$catatan->nama_file}");
    }

    public function unduh(Request $request): StreamedResponse|RedirectResponse
    {
        $nama = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ])['nama'];

        // basename() mencegah path traversal (mis. ../../.env) dari input pengguna
        $path = $this->folder().'/'.basename($nama);
        $disk = $this->disk();

        if (! str_ends_with(strtolower($path), '.zip') || ! $disk->exists($path)) {
            return back()->with('error', 'Berkas backup tidak ditemukan.');
        }

        return $disk->download($path);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $nama = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ])['nama'];

        $path = $this->folder().'/'.basename($nama);
        $disk = $this->disk();

        if (! str_ends_with(strtolower($path), '.zip') || ! $disk->exists($path)) {
            return back()->with('error', 'Berkas backup tidak ditemukan.');
        }

        $disk->delete($path);

        $this->audit->record(AuditLogService::AKSI_DELETE, 'backups', ['nama_file' => basename($nama)], null);

        return back()->with('success', 'Berkas backup dihapus: '.basename($nama));
    }
}
