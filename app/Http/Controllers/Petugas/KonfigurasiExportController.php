<?php

namespace App\Http\Controllers\Petugas;

use App\Exports\DkbSheetExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\PengaturanExportRequest;
use App\Models\DataAgregat;
use App\Models\DimWaktu;
use App\Models\KonfigurasiExport;
use App\Models\PengaturanExport;
use App\Services\AuditLogService;
use Database\Seeders\KonfigurasiExportSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Konfigurasi Unduh — Petugas mengatur BAGAIMANA berkas unduhan data agregat
 * ditulis. Elemennya menyesuaikan berkas Excel gaya DKB: No, judul kolom
 * Wilayah, sub-kolom Laki-laki/Perempuan/Jumlah, kolom "Jumlah Seluruhnya",
 * baris subtotal per kecamatan, baris total Kota. Sebagian (label L/P/Jumlah)
 * juga dipakai unduhan PDF. Plus pengaturan format berkas (default Excel/PDF,
 * batas baris PDF, orientasi PDF, teks kop PDF).
 *
 * Kumpulan elemen TETAP (lihat KonfigurasiExport::SUMBER) — tidak ada "tambah":
 * Petugas mengaktifkan/menonaktifkan (kecuali yang WAJIB) & mengganti label.
 * Aksi instan (tanpa tombol Simpan) selain form Pengaturan Format. Tercatat di
 * Audit Log. Mesin unggah DKB (tabel konfigurasi_import + Services/Import/*)
 * tidak tersentuh.
 */
class KonfigurasiExportController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function index(): View
    {
        $elemen     = KonfigurasiExport::terurut();
        $pengaturan = PengaturanExport::current();

        return view('petugas.konfigurasi-export.index', array_merge(
            compact('elemen', 'pengaturan'),
            $this->pratinjau(),
        ));
    }

    /**
     * Aksi kecil per baris elemen — memproses field yang dikirim saja:
     *  - aktif ("0"/"1") → ikutkan / hilangkan elemen
     *  - label (string)  → ganti teks label
     */
    public function atur(Request $request, KonfigurasiExport $konfigurasi_export): RedirectResponse
    {
        $data = $request->validate([
            'aktif' => ['sometimes', 'boolean'],
            'label' => ['sometimes', 'required', 'string', 'max:100'],
        ]);

        $sebelum = $konfigurasi_export->getAttributes();
        $namaElemen = KonfigurasiExport::SUMBER[$konfigurasi_export->kunci]['label'] ?? $konfigurasi_export->kunci;

        if (array_key_exists('label', $data)) {
            $konfigurasi_export->label = $data['label'];
            $konfigurasi_export->save();
            $this->audit->updated($konfigurasi_export, $sebelum);

            return back()->with('success', "Label elemen '{$namaElemen}' diubah menjadi '{$data['label']}'.");
        }

        if (array_key_exists('aktif', $data)) {
            if (! $data['aktif'] && $konfigurasi_export->wajib()) {
                return back()->with('error', "Elemen '{$namaElemen}' wajib ada, tidak bisa dinonaktifkan.");
            }

            $konfigurasi_export->aktif = $data['aktif'];
            $konfigurasi_export->save();
            $this->audit->updated($konfigurasi_export, $sebelum);

            return back()->with('success', "Elemen '{$namaElemen}' "
                .($data['aktif'] ? 'diaktifkan' : 'dinonaktifkan').'.');
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
            'aksi' => 'reset elemen berkas unduhan ke bawaan',
        ]);

        return back()->with('success', 'Elemen berkas unduhan dikembalikan ke bawaan.');
    }

    /**
     * Contoh mini berkas Excel gaya DKB memakai elemen yang SEDANG tersimpan —
     * dipilih indikator ber-rincian jenis kelamin per-kelurahan pertama yang
     * ada datanya (kalau tidak ada, indikator apa pun yang ada datanya).
     *
     * @return array{pratinjauNama: ?string, pratinjauGrid: array<int, array<int, mixed>>}
     */
    private function pratinjau(): array
    {
        $waktu = DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->first();

        if (! $waktu) {
            return ['pratinjauNama' => null, 'pratinjauGrid' => []];
        }

        $kandidat = ['status_kawin', 'agama', 'pendidikan', 'disabilitas'];
        $jenis = collect($kandidat)->first(fn ($j) => DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $j))
            ->where('waktu_id', $waktu->id)->exists());

        $jenis ??= DataAgregat::query()->where('waktu_id', $waktu->id)
            ->with('kategori:id,jenis_indikator')->first()?->kategori?->jenis_indikator;

        if (! $jenis) {
            return ['pratinjauNama' => null, 'pratinjauGrid' => []];
        }

        $sheet = new DkbSheetExport($jenis, str($jenis)->headline(), collect([$waktu]), null, 'Contoh');

        return [
            'pratinjauNama' => (string) str($jenis)->headline().' — '.$waktu->label,
            'pratinjauGrid' => array_slice($sheet->array(), 0, 16),
        ];
    }
}
