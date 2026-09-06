<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\KonfigurasiImportRequest;
use App\Models\KonfigurasiImport;
use App\Services\AuditLogService;
use App\Services\Import\PembacaSheetDkb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Kelola Konfigurasi Import — CRUD atas tabel konfigurasi_import lewat
 * browser, supaya Petugas bisa menambah/mengubah pemetaan sheet Excel baru
 * tanpa harus minta pengembang mengedit KonfigurasiImportSeeder.
 *
 * "Uji Coba Pemetaan" memakai PembacaSheetDkb::ujiPemetaan() yang sudah ada
 * (dipakai juga oleh proses import sungguhan) — halaman ini murni tampilan,
 * tidak ada logika pembacaan Excel baru yang ditulis di sini.
 */
class KonfigurasiImportController extends Controller
{
    private const FOLDER_UJI = 'uji-pemetaan';

    public function __construct(
        private readonly AuditLogService $audit,
        private readonly PembacaSheetDkb $pembaca,
    ) {
    }

    public function index(Request $request): View
    {
        $q          = trim((string) $request->query('q'));
        $namaProfil = $request->query('profil');
        $namaSheet  = $request->query('sheet');

        $konfigurasi = KonfigurasiImport::query()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('label', 'like', "%{$q}%")
                ->orWhere('jenis_indikator', 'like', "%{$q}%")
                ->orWhere('teks_header', 'like', "%{$q}%")))
            ->when($namaProfil, fn ($query) => $query->where('nama_profil', $namaProfil))
            ->when($namaSheet, fn ($query) => $query->where('nama_sheet', $namaSheet))
            ->orderBy('nama_profil')
            ->orderBy('nama_sheet')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $profilList = KonfigurasiImport::daftarProfil();
        $sheetList  = KonfigurasiImport::query()->distinct()->orderBy('nama_sheet')->pluck('nama_sheet');

        return view('petugas.konfigurasi-import.index', compact(
            'konfigurasi', 'profilList', 'sheetList', 'q', 'namaProfil', 'namaSheet',
        ));
    }

    public function create(): View
    {
        return view('petugas.konfigurasi-import.create', [
            'profilList' => KonfigurasiImport::daftarProfil(),
            'sheetList'  => KonfigurasiImport::query()->distinct()->orderBy('nama_sheet')->pluck('nama_sheet'),
        ]);
    }

    public function store(KonfigurasiImportRequest $request): RedirectResponse
    {
        $konfigurasi = KonfigurasiImport::create($request->validated());

        $this->audit->created($konfigurasi);

        return redirect()
            ->route('petugas.konfigurasi-import.index')
            ->with('success', "Konfigurasi untuk label '{$konfigurasi->label}' berhasil ditambahkan.");
    }

    public function edit(KonfigurasiImport $konfigurasi_import): View
    {
        return view('petugas.konfigurasi-import.edit', [
            'konfigurasi' => $konfigurasi_import,
            'profilList'  => KonfigurasiImport::daftarProfil(),
            'sheetList'   => KonfigurasiImport::query()->distinct()->orderBy('nama_sheet')->pluck('nama_sheet'),
        ]);
    }

    public function update(KonfigurasiImportRequest $request, KonfigurasiImport $konfigurasi_import): RedirectResponse
    {
        $sebelum = $konfigurasi_import->getAttributes();

        $konfigurasi_import->fill($request->validated());
        $konfigurasi_import->save();

        $this->audit->updated($konfigurasi_import, $sebelum);

        return redirect()
            ->route('petugas.konfigurasi-import.index')
            ->with('success', "Konfigurasi '{$konfigurasi_import->label}' berhasil diperbarui.");
    }

    public function destroy(KonfigurasiImport $konfigurasi_import): RedirectResponse
    {
        $this->audit->deleted($konfigurasi_import);
        $label = $konfigurasi_import->label;
        $konfigurasi_import->delete();

        return redirect()
            ->route('petugas.konfigurasi-import.index')
            ->with('success', "Konfigurasi '{$label}' berhasil dihapus.");
    }

    // ── Uji Coba Pemetaan ────────────────────────────────────────────────

    public function uji(): View
    {
        return view('petugas.konfigurasi-import.uji', [
            'profilList' => KonfigurasiImport::daftarProfil(),
            'hasil'      => null,
        ]);
    }

    public function ujiProses(Request $request): View
    {
        $data = $request->validate([
            'file'        => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
            'nama_profil' => ['required', 'string', 'exists:konfigurasi_import,nama_profil'],
            'nama_sheet'  => ['nullable', 'string'],
        ], [], ['file' => 'berkas', 'nama_profil' => 'profil', 'nama_sheet' => 'sheet']);

        $berkas = $request->file('file');
        $path   = $berkas->store(self::FOLDER_UJI);
        $full   = Storage::path($path);

        $sheetTersedia = $this->pembaca->daftarSheet($full);
        $sheetDiuji    = $data['nama_sheet'] ?: null;

        $hasil = null;

        if ($sheetDiuji) {
            $hasil = $this->pembaca->ujiPemetaan($full, $sheetDiuji, $data['nama_profil']);
        }

        // Berkas uji hanya perlu bertahan selama satu request — tidak ada
        // langkah pratinjau/konfirmasi terpisah seperti Mode A import.
        Storage::delete($path);

        return view('petugas.konfigurasi-import.uji', [
            'profilList'    => KonfigurasiImport::daftarProfil(),
            'sheetTersedia' => $sheetTersedia,
            'sheetDiuji'    => $sheetDiuji,
            'namaProfil'    => $data['nama_profil'],
            'namaBerkas'    => $berkas->getClientOriginalName(),
            'hasil'         => $hasil,
        ]);
    }
}
