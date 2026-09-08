<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\IndikatorRequest;
use App\Models\DataAgregat;
use App\Models\DimKategori;
use App\Models\DimWaktu;
use App\Models\DimWilayah;
use App\Models\Metadata;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * KF Kelola Indikator — tabel CRUD sederhana atas dim_kategori.
 *
 * Sengaja TIDAK ada hapus paksa untuk indikator yang masih dipakai
 * data_agregat: menonaktifkan (aktif=false) adalah cara yang diminta untuk
 * menyembunyikannya dari dashboard publik tanpa kehilangan datanya — mirip
 * pola guard FK yang sudah ada di WilayahController.
 */
class IndikatorController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly DashboardCacheService $cache,
    ) {
    }

    public function index(Request $request): View
    {
        $q     = trim((string) $request->query('q'));
        $jenis = $request->query('jenis');

        $indikator = DimKategori::query()
            ->withCount('dataAgregat')
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('label', 'like', "%{$q}%")
                ->orWhere('jenis_indikator', 'like', "%{$q}%")))
            ->when($jenis, fn ($query) => $query->where('jenis_indikator', $jenis))
            ->orderBy('jenis_indikator')
            ->orderBy('urutan')
            ->orderBy('label')
            ->paginate(20)
            ->withQueryString();

        $jenisList = DimKategori::query()->distinct()->orderBy('jenis_indikator')->pluck('jenis_indikator');

        // Dipakai menampilkan tautan "lihat/isi metadata" per baris tanpa
        // query N+1 — metadatas hanya sekitar puluhan baris, aman diambil sekaligus.
        $metadataAda = Metadata::pluck('id', 'jenis_indikator');

        return view('petugas.indikator.index', compact('indikator', 'jenisList', 'q', 'jenis', 'metadataAda'));
    }

    public function create(): View
    {
        $jenisList = DimKategori::query()->distinct()->orderBy('jenis_indikator')->pluck('jenis_indikator');

        return view('petugas.indikator.create', compact('jenisList'));
    }

    public function store(IndikatorRequest $request): RedirectResponse
    {
        $indikator = DimKategori::create($request->validated());

        $this->audit->created($indikator);
        $this->cache->flush();

        return redirect()
            ->route('petugas.indikator.index')
            ->with('success', "Indikator '{$indikator->label}' berhasil ditambahkan.");
    }

    public function edit(DimKategori $indikator): View
    {
        $jenisList = DimKategori::query()->distinct()->orderBy('jenis_indikator')->pluck('jenis_indikator');

        return view('petugas.indikator.edit', compact('indikator', 'jenisList'));
    }

    public function update(IndikatorRequest $request, DimKategori $indikator): RedirectResponse
    {
        $sebelum = $indikator->getAttributes();

        $indikator->fill($request->validated());
        $indikator->save();

        $this->audit->updated($indikator, $sebelum);
        // Toggle aktif/nonaktif mengubah apa yang muncul di seluruh agregasi
        // dashboard (scope ->aktif() dipakai di mana-mana) — flush juga di sini.
        $this->cache->flush();

        return redirect()
            ->route('petugas.indikator.index')
            ->with('success', "Indikator '{$indikator->label}' berhasil diperbarui.");
    }

    public function destroy(DimKategori $indikator): RedirectResponse
    {
        $jumlahData = $indikator->dataAgregat()->count();

        if ($jumlahData > 0) {
            return redirect()
                ->route('petugas.indikator.index')
                ->with('error', "Indikator '{$indikator->label}' tidak dapat dihapus karena masih dipakai {$jumlahData} baris data agregat. Nonaktifkan saja bila tidak ingin ditampilkan.");
        }

        $this->audit->deleted($indikator);
        $label = $indikator->label;
        $indikator->delete();
        $this->cache->flush();

        return redirect()
            ->route('petugas.indikator.index')
            ->with('success', "Indikator '{$label}' berhasil dihapus.");
    }

    // ── Input Data Manual ───────────────────────────────────────────────

    /**
     * Daftar nilai data_agregat untuk SATU indikator, lintas wilayah &
     * periode, plus form tambah/timpa satu nilai.
     */
    public function data(DimKategori $indikator): View
    {
        $data = DataAgregat::query()
            ->where('kategori_id', $indikator->id)
            ->with(['wilayah:id,nama_kelurahan,is_kota,is_kecamatan', 'waktu:id,label', 'pembuat:id,name', 'pengubah:id,name'])
            ->get()
            ->sortBy([['waktu.label', 'desc'], ['wilayah.nama_kelurahan', 'asc']]);

        $wilayahList = DimWilayah::orderByDesc('is_kota')->orderByDesc('is_kecamatan')->orderBy('nama_kelurahan')->get(['id', 'nama_kelurahan', 'is_kota', 'is_kecamatan']);
        $waktuList   = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->get(['id', 'label']);

        // Chart per kelurahan, dikelompokkan per periode (bukan digabung) —
        // sengaja HANYA kelurahan asli (bukan baris rekap kota/kecamatan),
        // supaya satu bar konsisten mewakili satu kelurahan seperti chart
        // lain di aplikasi ini. Dikirim sebagai {periode: {kelurahan: jumlah}}
        // supaya dropdown periode di Blade bisa mengganti chart client-side
        // tanpa reload, pola yang sama dengan "Cari periode" di Mobilitas.
        $chartPerPeriode = $data
            ->filter(fn (DataAgregat $d) => $d->wilayah && ! $d->wilayah->is_kota && ! $d->wilayah->is_kecamatan)
            ->groupBy(fn (DataAgregat $d) => $d->waktu->label ?? '—')
            ->map(fn ($rows) => $rows->sortByDesc('jumlah')->pluck('jumlah', 'wilayah.nama_kelurahan'));

        return view('petugas.indikator.data', compact('indikator', 'data', 'wilayahList', 'waktuList', 'chartPerPeriode'));
    }

    public function dataStore(Request $request, DimKategori $indikator): RedirectResponse
    {
        $validated = $request->validate([
            'wilayah_id' => ['required', 'integer', 'exists:dim_wilayah,id'],
            'waktu_id'   => ['required', 'integer', 'exists:dim_waktu,id'],
            'jumlah'     => ['required', 'integer', 'min:0', 'max:999999999'],
        ], [], ['wilayah_id' => 'wilayah', 'waktu_id' => 'periode', 'jumlah' => 'jumlah']);

        $existing = DataAgregat::where('wilayah_id', $validated['wilayah_id'])
            ->where('waktu_id', $validated['waktu_id'])
            ->where('kategori_id', $indikator->id)
            ->first();

        $wilayah = DimWilayah::find($validated['wilayah_id']);
        $waktu   = DimWaktu::find($validated['waktu_id']);

        if ($existing) {
            $sebelum = $existing->getAttributes();
            $nilaiLama = $existing->jumlah;

            $existing->fill([
                'jumlah'     => $validated['jumlah'],
                'updated_by' => $request->user()->id,
            ]);
            $existing->save();

            $this->audit->updated($existing, $sebelum);

            $pesan = "Nilai {$indikator->label} untuk {$wilayah->nama_kelurahan} / {$waktu->label} "
                ."diganti dari {$nilaiLama} menjadi {$validated['jumlah']} (input manual, menimpa nilai lama).";
        } else {
            $baru = DataAgregat::create([
                'wilayah_id'  => $validated['wilayah_id'],
                'waktu_id'    => $validated['waktu_id'],
                'kategori_id' => $indikator->id,
                'jumlah'      => $validated['jumlah'],
                'import_id'   => null,
                'created_by'  => $request->user()->id,
                'updated_by'  => $request->user()->id,
            ]);

            $this->audit->created($baru);

            $pesan = "Nilai {$indikator->label} untuk {$wilayah->nama_kelurahan} / {$waktu->label} "
                ."berhasil ditambahkan secara manual: {$validated['jumlah']}.";
        }

        $this->cache->flush();

        return redirect()
            ->route('petugas.indikator.data', $indikator)
            ->with('success', $pesan);
    }
}
