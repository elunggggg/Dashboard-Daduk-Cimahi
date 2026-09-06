<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\PengaturanTampilanRequest;
use App\Models\PengaturanTampilan;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function edit(): View
    {
        $pengaturan = PengaturanTampilan::current();

        return view('petugas.pengaturan.edit', compact('pengaturan'));
    }

    public function update(PengaturanTampilanRequest $request): RedirectResponse
    {
        $pengaturan = PengaturanTampilan::current();
        $sebelum    = $pengaturan->getAttributes();

        $this->terapkanUnggahan($request, $pengaturan, 'latar_belakang', 'hapus_latar');
        $this->terapkanUnggahan($request, $pengaturan, 'logo', 'hapus_logo');

        $pengaturan->save();

        $this->audit->updated($pengaturan, $sebelum);

        return redirect()
            ->route('petugas.pengaturan.edit')
            ->with('success', 'Tampilan Dashboard Publik berhasil diperbarui.');
    }

    /**
     * $kolom adalah nama field FORM ('logo', 'latar_belakang' — tanpa akhiran),
     * sedangkan kolom sungguhan di tabel `pengaturan_tampilan` berakhiran
     * `_path` (mis. `logo_path`). Keduanya sengaja dipisah di sini.
     */
    private function terapkanUnggahan(Request $request, PengaturanTampilan $pengaturan, string $kolom, string $fieldHapus): void
    {
        $kolomDb = $kolom.'_path';

        if ($request->boolean($fieldHapus)) {
            if ($pengaturan->$kolomDb) {
                Storage::disk('public')->delete($pengaturan->$kolomDb);
            }
            $pengaturan->$kolomDb = null;
        } elseif ($request->hasFile($kolom)) {
            if ($pengaturan->$kolomDb) {
                Storage::disk('public')->delete($pengaturan->$kolomDb);
            }
            $pengaturan->$kolomDb = $request->file($kolom)->store('pengaturan', 'public');
        }
    }
}
