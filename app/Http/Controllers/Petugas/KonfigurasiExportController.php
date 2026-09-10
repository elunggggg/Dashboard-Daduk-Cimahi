<?php

namespace App\Http\Controllers\Petugas;

use App\Exports\DataAgregatExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\PengaturanExportRequest;
use App\Models\KonfigurasiExport;
use App\Models\PengaturanExport;
use App\Services\AuditLogService;
use Database\Seeders\KonfigurasiExportSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Konfigurasi Export — Petugas mengatur BAGAIMANA berkas ekspor data agregat
 * ditulis: kolom mana yang ikut, urutannya, label header-nya, format angkanya,
 * plus pengaturan format berkas (default Excel/PDF, batas baris PDF, orientasi,
 * teks kop).
 *
 * Menggantikan halaman "Konfigurasi Import" yang salah konsep. Mesin import DKB
 * (tabel konfigurasi_import + Services/Import/*) tidak tersentuh — hanya
 * halaman UI-nya yang dihapus.
 *
 * Kumpulan kolom bersifat TETAP (9 sumber di data_agregat) — jadi tidak ada
 * "tambah kolom": Petugas mengaktifkan/menonaktifkan, mengurutkan (▲▼), dan
 * mengganti label. Semua aksi instan (tanpa tombol Simpan), tercatat di Audit
 * Log.
 */
class KonfigurasiExportController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function index(): View
    {
        $kolom      = KonfigurasiExport::query()->orderBy('urutan')->orderBy('id')->get();
        $pengaturan = PengaturanExport::current();

        // Pratinjau live: 15 baris pertama ekspor "semua indikator" (tanpa
        // filter) memakai konfigurasi kolom yang SEDANG tersimpan. Kolom
        // Laki-laki/Perempuan tidak muncul di sini karena baru relevan saat
        // mengekspor indikator yang punya rincian jenis kelamin.
        $contoh   = new DataAgregatExport(null, null, null);
        $pratinjauKolom = $contoh->kolomAktif();
        $pratinjauBaris = $contoh->barisTampil()->take(15);

        return view('petugas.konfigurasi-export.index', compact(
            'kolom', 'pengaturan', 'pratinjauKolom', 'pratinjauBaris',
        ));
    }

    /**
     * Aksi kecil per baris kolom — memproses field yang dikirim saja:
     *  - aktif ("0"/"1")        → tampil/sembunyi kolom
     *  - label (string)         → ganti teks header
     *  - arah ("naik"/"turun")  → tukar urutan dengan tetangga
     */
    public function atur(Request $request, KonfigurasiExport $konfigurasi_export): RedirectResponse
    {
        $data = $request->validate([
            'aktif' => ['sometimes', 'boolean'],
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'arah'  => ['sometimes', Rule::in(['naik', 'turun'])],
        ]);

        $sebelum = $konfigurasi_export->getAttributes();

        if (array_key_exists('label', $data)) {
            $konfigurasi_export->label = $data['label'];
            $konfigurasi_export->save();
            $this->audit->updated($konfigurasi_export, $sebelum);

            return back()->with('success', "Label kolom diubah menjadi '{$data['label']}'.");
        }

        if (array_key_exists('aktif', $data)) {
            // Jangan sampai semua kolom nonaktif — berkas ekspor jadi kosong.
            if (! $data['aktif'] && KonfigurasiExport::query()->aktif()->count() <= 1) {
                return back()->with('error', 'Minimal satu kolom harus tetap aktif.');
            }

            $konfigurasi_export->aktif = $data['aktif'];
            $konfigurasi_export->save();
            $this->audit->updated($konfigurasi_export, $sebelum);

            return back()->with('success', 'Kolom "'.$konfigurasi_export->label.'" '
                .($data['aktif'] ? 'diaktifkan' : 'dinonaktifkan').'.');
        }

        if (array_key_exists('arah', $data)) {
            $this->geser($konfigurasi_export, $data['arah']);

            return back()->with('success', 'Urutan kolom diperbarui.');
        }

        return back();
    }

    public function simpanPengaturan(PengaturanExportRequest $request): RedirectResponse
    {
        $pengaturan = PengaturanExport::current();
        $sebelum    = $pengaturan->getAttributes();

        $pengaturan->fill($request->validated())->save();
        $this->audit->updated($pengaturan, $sebelum);

        return back()->with('success', 'Pengaturan format unduhan disimpan.');
    }

    public function reset(): RedirectResponse
    {
        DB::transaction(function () {
            KonfigurasiExport::query()->delete();
            (new KonfigurasiExportSeeder)->run();
        });

        $this->audit->record(AuditLogService::AKSI_UPDATE, 'konfigurasi_export', null, [
            'aksi' => 'reset ke kolom bawaan',
        ]);

        return back()->with('success', 'Konfigurasi kolom unduhan dikembalikan ke bawaan.');
    }

    /** Tukar urutan satu kolom dengan tetangga di atas/bawahnya, lalu nomori ulang 10,20,30… */
    private function geser(KonfigurasiExport $kolom, string $arah): void
    {
        $ids = KonfigurasiExport::query()->orderBy('urutan')->orderBy('id')->pluck('id')->all();
        $i   = array_search($kolom->id, $ids, true);
        $j   = $arah === 'naik' ? $i - 1 : $i + 1;

        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }

        [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $pos => $id) {
                KonfigurasiExport::whereKey($id)->update(['urutan' => ($pos + 1) * 10]);
            }
        });
    }
}
