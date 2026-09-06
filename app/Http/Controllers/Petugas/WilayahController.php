<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\WilayahRequest;
use App\Models\AliasWilayah;
use App\Models\DimWilayah;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WilayahController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function index(Request $request): View
    {
        $q         = trim((string) $request->query('q'));
        $kecamatan = $request->query('kecamatan');

        $wilayah = DimWilayah::query()
            ->kelurahan()
            ->withCount('dataAgregat')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('nama_kelurahan', 'like', "%{$q}%")
                ->orWhere('nama_kecamatan', 'like', "%{$q}%")
                ->orWhere('kode_kemendagri', 'like', "%{$q}%")))
            ->when($kecamatan, fn ($query) => $query->where('nama_kecamatan', $kecamatan))
            ->orderBy('nama_kecamatan')
            ->orderBy('nama_kelurahan')
            ->paginate(15)
            ->withQueryString();

        $kecamatanList = DimWilayah::query()
            ->kelurahan()
            ->distinct()
            ->orderBy('nama_kecamatan')
            ->pluck('nama_kecamatan');

        return view('petugas.wilayah.index', compact('wilayah', 'kecamatanList', 'q', 'kecamatan'));
    }

    public function create(): View
    {
        $kecamatanList = DimWilayah::query()
            ->kelurahan()
            ->distinct()
            ->orderBy('nama_kecamatan')
            ->pluck('nama_kecamatan');

        return view('petugas.wilayah.create', compact('kecamatanList'));
    }

    public function store(WilayahRequest $request): RedirectResponse
    {
        $wilayah = DimWilayah::create($request->validated());

        $this->audit->created($wilayah);

        return redirect()
            ->route('petugas.wilayah.index')
            ->with('success', "Kelurahan {$wilayah->nama_kelurahan} berhasil ditambahkan.");
    }

    public function edit(DimWilayah $wilayah): View
    {
        abort_if($wilayah->is_kota || $wilayah->is_kecamatan, 404);

        $kecamatanList = DimWilayah::query()
            ->kelurahan()
            ->distinct()
            ->orderBy('nama_kecamatan')
            ->pluck('nama_kecamatan');

        $wilayah->load('alias');

        return view('petugas.wilayah.edit', compact('wilayah', 'kecamatanList'));
    }

    /**
     * Tambah ejaan alternatif nama kelurahan.
     *
     * Inilah tombol yang dirujuk pesan galat import "…tambahkan ejaan yang
     * dipakai berkas sebagai alias". Tanpa ini, satu ejaan menyimpang di berkas
     * Excel hanya bisa diperbaiki lewat seeder — artinya harus lewat pengembang.
     */
    public function tambahAlias(Request $request, DimWilayah $wilayah): RedirectResponse
    {
        $data = $request->validate([
            'nama_alias' => ['required', 'string', 'max:100'],
        ], [], ['nama_alias' => 'nama alias']);

        $nama = AliasWilayah::normalkan($data['nama_alias']);

        // Diperiksa manual, bukan lewat Rule::unique, karena yang disimpan
        // adalah bentuk ternormalisasi — "Karang Mekar" dan "KARANG  MEKAR"
        // menghasilkan baris yang sama dan harus ditolak keduanya.
        $bentrok = AliasWilayah::where('nama_alias', $nama)->with('wilayah')->first();

        if ($bentrok) {
            return back()->with('error', $bentrok->wilayah_id === $wilayah->id
                ? "Alias '{$nama}' sudah terdaftar untuk kelurahan ini."
                : "Alias '{$nama}' sudah dipakai kelurahan {$bentrok->wilayah?->nama_kelurahan}.");
        }

        $alias = $wilayah->alias()->create(['nama_alias' => $nama]);

        $this->audit->created($alias);

        return back()->with('success', "Alias '{$nama}' ditambahkan untuk {$wilayah->nama_kelurahan}.");
    }

    public function hapusAlias(AliasWilayah $alias): RedirectResponse
    {
        $this->audit->deleted($alias);

        $nama      = $alias->nama_alias;
        $wilayahId = $alias->wilayah_id;
        $alias->delete();

        return redirect()
            ->route('petugas.wilayah.edit', $wilayahId)
            ->with('success', "Alias '{$nama}' dihapus.");
    }

    public function update(WilayahRequest $request, DimWilayah $wilayah): RedirectResponse
    {
        $sebelum = $wilayah->getAttributes();

        $wilayah->fill($request->validated());
        $wilayah->save();

        $this->audit->updated($wilayah, $sebelum);

        return redirect()
            ->route('petugas.wilayah.index')
            ->with('success', "Kelurahan {$wilayah->nama_kelurahan} berhasil diperbarui.");
    }

    public function destroy(DimWilayah $wilayah): RedirectResponse
    {
        abort_if($wilayah->is_kota || $wilayah->is_kecamatan, 404);

        // Wilayah yang masih dirujuk data agregat tidak boleh dihapus —
        // menghapusnya akan membuat angka pada dashboard hilang tanpa jejak.
        $jumlahData = $wilayah->dataAgregat()->count();

        if ($jumlahData > 0) {
            return redirect()
                ->route('petugas.wilayah.index')
                ->with('error', "Kelurahan {$wilayah->nama_kelurahan} tidak dapat dihapus karena masih memiliki {$jumlahData} baris data agregat.");
        }

        $this->audit->deleted($wilayah);
        $nama = $wilayah->nama_kelurahan;
        $wilayah->delete();

        return redirect()
            ->route('petugas.wilayah.index')
            ->with('success', "Kelurahan {$nama} berhasil dihapus.");
    }
}
