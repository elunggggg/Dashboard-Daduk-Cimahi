<?php

namespace App\Http\Controllers\Petugas;

use App\Exports\TemplateImportExport;
use App\Http\Controllers\Controller;
use App\Imports\DataAgregatImport;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\ImportExcel;
use App\Models\KonfigurasiImport;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use App\Services\Import\PembacaSheetDkb;
use App\Services\Import\PenyimpanDataAgregat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Dua mode import, sesuai Prinsip 7.
 *
 * MODE A ("File DKB Mentah") — jalur utama. Berkas asli dari Disdukcapil
 * diunggah apa adanya, lalu diparse memakai profil di konfigurasi_import.
 * Alurnya tiga langkah: unggah → pratinjau → konfirmasi. Tidak ada satu angka
 * pun yang masuk database sebelum Petugas menekan konfirmasi.
 *
 * MODE B ("Template Sederhana") — jalur cadangan sekali jalan untuk berkas flat
 * yang sudah Petugas rapikan manual. Sengaja tidak diberi pratinjau: formatnya
 * sudah baku dan bersifat all-or-nothing, jadi tidak ada yang perlu diputuskan
 * di tengah jalan.
 */
class ImportController extends Controller
{
    /** Berkas unggahan disimpan di disk privat — bukan di public/. */
    private const FOLDER_BERKAS = 'import-dkb';

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly PenyimpanDataAgregat $penyimpan,
        private readonly DashboardCacheService $cache,
    ) {
    }

    public function index(): View
    {
        // withCount + eager load: tanpa ini tiap baris tabel memicu query sendiri
        // untuk menghitung data_agregat dan mengambil nama penghapusnya.
        $riwayat = ImportExcel::with(['user:id,name', 'penghapus:id,name'])
            ->withCount('dataAgregat')
            ->latest('id')
            ->paginate(10);

        // Ditampilkan sebagai rujukan penulisan kolom jenis_indikator / label (Mode B)
        $referensi = DimKategori::orderBy('jenis_indikator')
            ->orderBy('label')
            ->get()
            ->groupBy('jenis_indikator');

        $profil = KonfigurasiImport::daftarProfil();

        return view('petugas.import.index', compact('riwayat', 'referensi', 'profil'));
    }

    // ── Unduhan template Mode B ───────────────────────────────────────────────

    public function template(): BinaryFileResponse
    {
        return Excel::download(new TemplateImportExport(false), 'template-import-kosong.xlsx');
    }

    public function templateContoh(): BinaryFileResponse
    {
        return Excel::download(new TemplateImportExport(true), 'template-import-contoh-terisi.xlsx');
    }

    // ── MODE A: unggah → pratinjau → konfirmasi ───────────────────────────────

    /**
     * Langkah 1: simpan berkas, parse, lalu antar Petugas ke halaman pratinjau.
     *
     * Berkasnya disimpan permanen (bukan di temp) karena halaman pratinjau dan
     * halaman konfirmasi sama-sama perlu mem-parse ulang berkas yang sama.
     * Menyimpan hasil parse di session bukan pilihan: satu berkas DKB penuh
     * menghasilkan ribuan baris dan akan melewati batas ukuran session.
     */
    public function storeDkb(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file'        => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'nama_profil' => ['required', 'string', 'exists:konfigurasi_import,nama_profil'],
            'tahun'       => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'semester'    => ['nullable', 'integer', 'in:1,2'],
        ], [], [
            'file'        => 'berkas',
            'nama_profil' => 'profil pemetaan',
        ]);

        $berkas = $request->file('file');
        $path   = $berkas->store(self::FOLDER_BERKAS);

        $import = ImportExcel::create([
            'user_id'     => $request->user()->id,
            'nama_file'   => $berkas->getClientOriginalName(),
            'path_file'   => $path,
            'mode'        => ImportExcel::MODE_DKB,
            'nama_profil' => $data['nama_profil'],
            'tahun'       => $data['tahun'] ?? null,
            'semester'    => $data['semester'] ?? null,
            'status'      => ImportExcel::STATUS_PRATINJAU,
        ]);

        return redirect()->route('petugas.import.pratinjau', $import);
    }

    /**
     * Langkah 2: tampilkan apa yang AKAN terjadi, tanpa mengubah apa pun.
     */
    public function pratinjau(Request $request, ImportExcel $import): View|RedirectResponse
    {
        if (! $import->bisaDikonfirmasi()) {
            return redirect()->route('petugas.import.index')
                ->with('error', 'Import ini sudah diselesaikan atau dibatalkan, jadi tidak bisa dipratinjau lagi.');
        }

        $hasil = $this->parse($import, $request);

        if ($hasil === null) {
            return redirect()->route('petugas.import.index')
                ->with('error', 'Berkas import tidak ditemukan lagi di penyimpanan. Silakan unggah ulang.');
        }

        // Deteksi duplikat hanya masuk akal kalau periodenya sudah pasti.
        if (! $hasil->adaGalat()) {
            $this->penyimpan->deteksiDuplikat($hasil);
        }

        return view('petugas.import.pratinjau', compact('import', 'hasil'));
    }

    /**
     * Langkah 3: simpan, dalam satu transaction.
     */
    public function konfirmasi(Request $request, ImportExcel $import): RedirectResponse
    {
        if (! $import->bisaDikonfirmasi()) {
            return redirect()->route('petugas.import.index')
                ->with('error', 'Import ini sudah diselesaikan atau dibatalkan.');
        }

        $data = $request->validate([
            'tahun'         => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester'      => ['required', 'integer', 'in:1,2'],
            'mode_duplikat' => ['required', 'in:timpa,lewati'],
        ], [], [
            'mode_duplikat' => 'penanganan data yang sudah ada',
        ]);

        $hasil = $this->parse($import, $request);

        if ($hasil === null) {
            return redirect()->route('petugas.import.index')
                ->with('error', 'Berkas import tidak ditemukan lagi di penyimpanan. Silakan unggah ulang.');
        }

        // Diperiksa ulang di sini, bukan hanya di halaman pratinjau: berkas atau
        // konfigurasinya bisa saja berubah di antara dua langkah tersebut.
        if ($hasil->adaGalat()) {
            return back()->with('error', 'Import dibatalkan — masih ada galat struktur pada berkas.');
        }

        try {
            $ringkas = $this->penyimpan->simpan($hasil, $import, $data['mode_duplikat']);
        } catch (Throwable $e) {
            $import->update([
                'status'      => ImportExcel::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menyimpan data: '.$e->getMessage().' Tidak ada data yang berubah.');
        }

        return redirect()->route('petugas.import.index')->with(
            'success',
            "Import berhasil — {$ringkas['baru']} baris baru, {$ringkas['diperbarui']} diperbarui, "
            ."{$ringkas['dilewati']} dilewati (Semester {$data['semester']} Tahun {$data['tahun']})."
        );
    }

    /**
     * Buang seluruh baris data_agregat yang berasal dari satu import.
     *
     * Ini jalan keluar untuk kasus paling manusiawi: Petugas sudah menekan
     * konfirmasi, lalu sadar berkas atau periodenya salah. Tanpa ini satu-satunya
     * pemulihan adalah menyunting database langsung.
     *
     * Dua hal yang perlu dipahami dan sengaja dinyatakan di layar:
     *
     * 1. Yang terhapus adalah baris yang stempel `import_id`-nya MASIH milik
     *    import ini. Baris yang sudah ditimpa import lain yang lebih baru sudah
     *    berpindah kepemilikan, jadi tidak ikut terbawa — perilaku yang benar,
     *    karena angka yang berlaku sekarang bukan lagi hasil import ini.
     * 2. Untuk baris yang dulu MENIMPA nilai lama (mode "timpa"), nilai lamanya
     *    tidak dikembalikan — aksi ini menghapus, bukan membatalkan. Nilai
     *    sebelumnya masih bisa dilihat di audit log bila perlu dipulihkan manual.
     */
    public function hapusData(Request $request, ImportExcel $import): RedirectResponse
    {
        if (! $import->bisaHapusData()) {
            return redirect()->route('petugas.import.index')
                ->with('error', 'Import ini tidak punya data yang bisa dihapus — mungkin sudah dihapus sebelumnya, atau seluruh barisnya sudah ditimpa import yang lebih baru.');
        }

        $ringkas = DB::transaction(function () use ($request, $import) {
            $jumlah = $import->dataAgregat()->count();

            // Periode yang mungkin jadi kosong setelah penghapusan — dikumpulkan
            // SEBELUM baris-barisnya hilang, karena sesudahnya tidak terlacak lagi.
            $periodeTerdampak = $import->dataAgregat()
                ->distinct()
                ->pluck('waktu_id')
                ->all();

            $import->dataAgregat()->delete();

            // Periode tanpa data tidak boleh menyisa di dropdown filter: ia akan
            // tampil sebagai pilihan yang isinya kosong.
            $periodeDibuang = [];

            foreach (DimWaktu::whereIn('id', $periodeTerdampak)->get() as $waktu) {
                if (! $waktu->dataAgregat()->exists()) {
                    $periodeDibuang[] = $waktu->label;
                    $waktu->delete();
                }
            }

            $import->update([
                'data_dihapus_pada'    => now(),
                'dihapus_oleh'         => $request->user()->id,
                'jumlah_baris_dihapus' => $jumlah,
            ]);

            $this->audit->record(AuditLogService::AKSI_DELETE, 'data_agregat', null, [
                'alasan'          => 'penghapusan data hasil import',
                'import_id'       => $import->id,
                'nama_file'       => $import->nama_file,
                'periode'         => "Semester {$import->semester} Tahun {$import->tahun}",
                'baris_dihapus'   => $jumlah,
                'periode_dibuang' => $periodeDibuang,
            ]);

            return ['jumlah' => $jumlah, 'periode_dibuang' => $periodeDibuang];
        });

        // Data_agregat berubah (baris terhapus) — cache dashboard yang lama
        // harus dianggap basi, sama seperti setelah import berhasil.
        $this->cache->flush();

        $pesan = "{$ringkas['jumlah']} baris data dari berkas {$import->nama_file} sudah dihapus.";

        if ($ringkas['periode_dibuang'] !== []) {
            $pesan .= ' Periode '.implode(', ', $ringkas['periode_dibuang'])
                .' ikut dibuang karena tidak punya data lagi.';
        }

        return redirect()->route('petugas.import.index')->with('success', $pesan.' Silakan unggah berkas yang benar.');
    }

    public function batal(ImportExcel $import): RedirectResponse
    {
        if (! $import->bisaDikonfirmasi()) {
            return redirect()->route('petugas.import.index')
                ->with('error', 'Import ini sudah diselesaikan atau dibatalkan.');
        }

        // Berkasnya ikut dibuang: import yang dibatalkan tidak menyisakan data
        // apa pun, jadi tidak ada yang perlu ditelusuri lagi darinya.
        if ($import->path_file) {
            Storage::delete($import->path_file);
        }

        $import->update([
            'status'    => ImportExcel::STATUS_DIBATALKAN,
            'path_file' => null,
        ]);

        return redirect()->route('petugas.import.index')
            ->with('success', 'Import dibatalkan. Tidak ada data yang diubah.');
    }

    /**
     * Parse ulang berkas milik sebuah catatan import.
     *
     * Mengembalikan null bila berkasnya sudah tidak ada di penyimpanan — bisa
     * terjadi kalau storage dibersihkan manual di antara dua langkah.
     */
    private function parse(ImportExcel $import, Request $request): ?\App\Services\Import\HasilPratinjau
    {
        if (! $import->path_file || ! Storage::exists($import->path_file)) {
            return null;
        }

        // Isian Petugas di form menang atas nilai yang tersimpan, dan keduanya
        // menang atas hasil deteksi otomatis dari judul sheet (Prinsip 4).
        $tahun    = $request->integer('tahun') ?: $import->tahun;
        $semester = $request->integer('semester') ?: $import->semester;

        return (new PembacaSheetDkb())->baca(
            Storage::path($import->path_file),
            $import->nama_profil ?? KonfigurasiImport::PROFIL_BAWAAN,
            $tahun ?: null,
            $semester ?: null,
            // Berkas di storage bernama acak; nama aslinya sering memuat periode
            // dan dipakai sebagai pembanding judul di dalam sheet.
            $import->nama_file,
        );
    }

    // ── MODE B: template flat, sekali jalan ───────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ], [], ['file' => 'berkas']);

        $berkas = $request->file('file');

        $catatan = ImportExcel::create([
            'user_id'   => $request->user()->id,
            'nama_file' => $berkas->getClientOriginalName(),
            'mode'      => ImportExcel::MODE_TEMPLATE,
            'status'    => ImportExcel::STATUS_PRATINJAU,
        ]);

        $import = new DataAgregatImport;

        try {
            Excel::import($import, $berkas);
        } catch (Throwable $e) {
            $catatan->update([
                'status'      => ImportExcel::STATUS_GAGAL,
                'pesan_error' => 'Berkas tidak dapat dibaca: '.$e->getMessage(),
            ]);

            return back()->with('error', 'Berkas tidak dapat dibaca. Pastikan formatnya .xlsx, .xls, atau .csv yang valid.');
        }

        if ($import->gagal()) {
            // Batas 20 agar pesan error tidak membanjiri layar
            $ditampilkan = array_slice($import->errors, 0, 20);
            $sisa        = count($import->errors) - count($ditampilkan);

            $catatan->update([
                'status'       => ImportExcel::STATUS_GAGAL,
                'jumlah_baris' => $import->jumlahBaris,
                'pesan_error'  => implode("\n", $import->errors),
            ]);

            return back()
                ->with('error', 'Import dibatalkan — tidak ada data yang diubah.')
                ->with('import_errors', $ditampilkan)
                ->with('import_errors_sisa', max(0, $sisa));
        }

        try {
            DB::transaction(fn () => $import->simpan($catatan->id, $request->user()->id));
        } catch (Throwable $e) {
            $catatan->update(['status' => ImportExcel::STATUS_GAGAL, 'pesan_error' => $e->getMessage()]);

            return back()->with('error', 'Gagal menyimpan data: '.$e->getMessage());
        }

        $catatan->update([
            'status'       => ImportExcel::STATUS_BERHASIL,
            'jumlah_baris' => $import->jumlahBaris,
        ]);

        $this->audit->record(AuditLogService::AKSI_IMPORT, 'data_agregat', null, [
            'nama_file'   => $catatan->nama_file,
            'mode'        => ImportExcel::MODE_TEMPLATE,
            'baris'       => $import->jumlahBaris,
            'baru'        => $import->jumlahBaru,
            'diperbarui'  => $import->jumlahDiperbarui,
        ]);

        // Mode B menulis data_agregat lewat DataAgregatImport::simpan(), bukan
        // PenyimpanDataAgregat — flush cache di sini juga (sama seperti Mode A).
        $this->cache->flush();

        return redirect()
            ->route('petugas.import.index')
            ->with('success', "Import selesai — {$import->jumlahBaris} baris diproses "
                ."({$import->jumlahBaru} baru, {$import->jumlahDiperbarui} diperbarui).");
    }
}
