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
 * "Bagian Dashboard" — Petugas mengatur tiap section grafik/tabel halaman
 * publik: tampil/sembunyi, pindah halaman (Demografi <-> Sosial), geser urutan,
 * ubah lebar. Semua aksi kecil & instan (satu klik → simpan → kembali).
 *
 * Daftar sectionnya diisi otomatis oleh komponen <x-seksi> begitu halaman
 * publik dibuka — "Reset" mengosongkan tabel supaya terbentuk ulang dari nilai
 * bawaan yang dideklarasikan di Blade.
 */
class SeksiDashboardController extends Controller
{
    private const HALAMAN_CAIR = ['demografi', 'sosial'];

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
     * Satu endpoint untuk semua perubahan pada satu bagian. Field yang dikirim
     * saja yang diproses:
     *  - tampil  : "0"/"1"
     *  - halaman : "demografi"|"sosial" (pindah; hanya sah antar halaman cair)
     *  - lebar   : "sepertiga"|"separuh"|"penuh"
     *  - arah    : "naik"|"turun" (tukar urutan dengan tetangga di halaman sama)
     */
    public function atur(Request $request, SeksiDashboard $seksi): RedirectResponse
    {
        $sebelum = $seksi->getAttributes();

        if ($request->has('tampil')) {
            $seksi->tampil = $request->boolean('tampil');
        }

        if ($request->filled('halaman')
            && in_array($request->input('halaman'), self::HALAMAN_CAIR, true)
            && in_array($seksi->halaman, self::HALAMAN_CAIR, true)
            && $request->input('halaman') !== $seksi->halaman) {
            $seksi->halaman = $request->input('halaman');
            // Taruh di paling bawah halaman tujuan.
            $seksi->urutan = ((int) SeksiDashboard::where('halaman', $seksi->halaman)->max('urutan')) + 10;
        }

        if ($request->filled('lebar')
            && in_array($request->input('lebar'), SeksiDashboardRegistry::LEBAR_VALID, true)) {
            $seksi->lebar = $request->input('lebar');
        }

        if ($seksi->isDirty()) {
            $seksi->save();
            $this->audit->updated($seksi, $sebelum);
        }

        if (in_array($request->input('arah'), ['naik', 'turun'], true)) {
            $this->geser($seksi, $request->input('arah'));
        }

        return back()->with('success', 'Bagian "'.$seksi->judul.'" diperbarui.');
    }

    /** Tukar posisi $seksi dengan tetangga di atas/bawahnya (halaman sama). */
    private function geser(SeksiDashboard $seksi, string $arah): void
    {
        $grup = SeksiDashboard::where('halaman', $seksi->halaman)
            ->orderBy('urutan')->orderBy('id')->get()->values();

        $idx  = $grup->search(fn (SeksiDashboard $s) => $s->id === $seksi->id);
        $lain = $arah === 'naik' ? $idx - 1 : $idx + 1;

        if ($idx === false || $lain < 0 || $lain >= $grup->count()) {
            return;
        }

        // Susun ulang lalu tulis urutan berjenjang (0,10,20,...) — tahan
        // terhadap nilai urutan yang kebetulan sama/berantakan.
        $baru = $grup->all();
        [$baru[$idx], $baru[$lain]] = [$baru[$lain], $baru[$idx]];

        foreach ($baru as $i => $s) {
            if ((int) $s->urutan !== $i * 10) {
                $s->forceFill(['urutan' => $i * 10])->save();
            }
        }
    }

    /**
     * Kembalikan SEMUA bagian ke tata letak AWAL — persis seperti sebelum fitur
     * "Bagian Dashboard" ada. Kosongkan tabel lalu isi ulang dari
     * SeksiDashboardRegistry::BAWAAN (tampil semua, halaman/urutan/lebar asal).
     */
    public function reset(): RedirectResponse
    {
        $jumlah = SeksiDashboard::count();

        \Illuminate\Support\Facades\DB::transaction(function () {
            SeksiDashboard::query()->delete();
            $this->registry->seedBawaan();
        });

        $this->audit->record(
            AuditLogService::AKSI_DELETE,
            'seksi_dashboard',
            null,
            ['alasan' => 'reset tata letak bagian dashboard ke keadaan awal', 'baris_lama' => $jumlah],
        );

        return redirect()
            ->route('petugas.seksi.index')
            ->with('success', 'Tata letak dashboard dikembalikan ke keadaan awal (semua bagian tampil, urutan & lebar asal).');
    }
}
