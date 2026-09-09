<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\SeksiDashboard;
use App\Services\AuditLogService;
use App\Services\SeksiDashboardRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Bagian Dashboard" — Petugas menyalakan/mematikan tiap section grafik/tabel
 * di halaman publik Demografi, Sosial, Mobilitas. Daftar sectionnya diisi
 * otomatis oleh komponen <x-seksi> begitu halaman publik dibuka, jadi kalau
 * daftar di sini terasa kurang lengkap, buka dulu halaman publik terkait.
 */
class SeksiDashboardController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly SeksiDashboardRegistry $registry,
    ) {
    }

    public function index(): View
    {
        $seksiPerHalaman = $this->registry->semua();

        return view('petugas.seksi.index', compact('seksiPerHalaman'));
    }

    /**
     * Simpan status tampil + halaman + urutan + lebar seluruh bagian sekaligus.
     * - `tampil[]`  : daftar id yang tercentang (sisanya = sembunyi)
     * - `halaman[id]`: 'demografi' | 'sosial' (pindah antar halaman; hanya untuk
     *   bagian yang memang di halaman ber-grid cair — bagian Mobilitas TIDAK
     *   bisa dipindah karena halaman itu memakai mesin render sendiri)
     * - `urutan[id]`, `lebar[id]`
     */
    public function update(Request $request): RedirectResponse
    {
        $tampilIds = collect($request->input('tampil', []))->map(fn ($v) => (int) $v)->all();
        $halaman   = (array) $request->input('halaman', []);
        $urutan    = (array) $request->input('urutan', []);
        $lebar     = (array) $request->input('lebar', []);

        $lebarValid   = \App\Services\SeksiDashboardRegistry::LEBAR_VALID;
        $halamanCair  = ['demografi', 'sosial'];
        $berubah      = 0;

        foreach (SeksiDashboard::all() as $seksi) {
            $sebelum = $seksi->getAttributes();

            $seksi->tampil = in_array($seksi->id, $tampilIds, true);

            // Pindah halaman hanya sah demografi <-> sosial.
            if (in_array($seksi->halaman, $halamanCair, true)
                && isset($halaman[$seksi->id])
                && in_array($halaman[$seksi->id], $halamanCair, true)) {
                $seksi->halaman = $halaman[$seksi->id];
            }

            if (isset($urutan[$seksi->id]) && is_numeric($urutan[$seksi->id])) {
                $seksi->urutan = max(0, min(9999, (int) $urutan[$seksi->id]));
            }

            if (isset($lebar[$seksi->id]) && in_array($lebar[$seksi->id], $lebarValid, true)) {
                $seksi->lebar = $lebar[$seksi->id];
            }

            if ($seksi->isDirty()) {
                $seksi->save();
                $this->audit->updated($seksi, $sebelum);
                $berubah++;
            }
        }

        return redirect()
            ->route('petugas.seksi.index')
            ->with('success', $berubah === 0 ? 'Tidak ada perubahan.' : "{$berubah} bagian dashboard diperbarui.");
    }

    /** Toggle satu bagian (dipakai tombol cepat per baris). */
    public function toggle(SeksiDashboard $seksi): RedirectResponse
    {
        $sebelum = $seksi->getAttributes();
        $seksi->tampil = ! $seksi->tampil;
        $seksi->save();
        $this->audit->updated($seksi, $sebelum);

        $status = $seksi->tampil ? 'ditampilkan' : 'disembunyikan';

        return back()->with('success', "Bagian \"{$seksi->judul}\" {$status}.");
    }
}
