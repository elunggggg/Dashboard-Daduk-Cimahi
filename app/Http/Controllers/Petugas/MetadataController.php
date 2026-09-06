<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\MetadataRequest;
use App\Models\Metadata;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetadataController extends Controller
{
    private const DAFTAR_MODUL = ['Demografi', 'Sosial', 'Mobilitas'];

    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function index(Request $request): View
    {
        $q     = trim((string) $request->query('q'));
        $modul = $request->query('modul');

        $metadata = Metadata::query()
            ->when($q, fn ($query) => $query->where(fn ($m) => $m
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('jenis_indikator', 'like', "%{$q}%")))
            ->when($modul, fn ($query) => $query->where('modul', $modul))
            ->orderBy('modul')
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('petugas.metadata.index', [
            'metadata'   => $metadata,
            'q'          => $q,
            'modul'      => $modul,
            'modulList'  => self::DAFTAR_MODUL,
        ]);
    }

    public function create(): View
    {
        return view('petugas.metadata.create', ['modulList' => self::DAFTAR_MODUL]);
    }

    public function store(MetadataRequest $request): RedirectResponse
    {
        $metadata = Metadata::create($request->validated());

        $this->audit->created($metadata);

        return redirect()
            ->route('petugas.metadata.index')
            ->with('success', "Metadata '{$metadata->nama}' berhasil ditambahkan.");
    }

    public function edit(Metadata $metadata): View
    {
        return view('petugas.metadata.edit', ['metadata' => $metadata, 'modulList' => self::DAFTAR_MODUL]);
    }

    public function update(MetadataRequest $request, Metadata $metadata): RedirectResponse
    {
        $sebelum = $metadata->getAttributes();

        $metadata->fill($request->validated());
        $metadata->save();

        $this->audit->updated($metadata, $sebelum);

        return redirect()
            ->route('petugas.metadata.index')
            ->with('success', "Metadata '{$metadata->nama}' berhasil diperbarui.");
    }

    public function destroy(Metadata $metadata): RedirectResponse
    {
        $this->audit->deleted($metadata);
        $nama = $metadata->nama;
        $metadata->delete();

        return redirect()
            ->route('petugas.metadata.index')
            ->with('success', "Metadata '{$nama}' berhasil dihapus.");
    }
}
