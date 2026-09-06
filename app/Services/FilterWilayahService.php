<?php

namespace App\Services;

use App\Models\DimWaktu;
use App\Models\DimWilayah;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FilterWilayahService
{
    public function getKecamatanList(): Collection
    {
        return DimWilayah::query()
            ->kelurahan()
            ->select('nama_kecamatan')
            ->distinct()
            ->orderBy('nama_kecamatan')
            ->pluck('nama_kecamatan');
    }

    public function getWilayahList(?string $kecamatan = null): Collection
    {
        return DimWilayah::query()
            ->kelurahan()
            ->when($kecamatan, fn ($q) => $q->where('nama_kecamatan', $kecamatan))
            ->orderBy('nama_kecamatan')
            ->orderBy('nama_kelurahan')
            ->get(['id', 'nama_kelurahan', 'nama_kecamatan']);
    }

    public function getWaktuList(): Collection
    {
        return DimWaktu::query()
            ->orderBy('tahun', 'desc')
            ->orderBy('semester', 'desc')
            ->get();
    }

    public function getLatestWaktu(): ?DimWaktu
    {
        return DimWaktu::query()
            ->orderBy('tahun', 'desc')
            ->orderBy('semester', 'desc')
            ->first();
    }

    /**
     * Periode yang sedang dipilih; null berarti SEMUA periode.
     *
     * Membedakan tiga keadaan yang di URL sama-sama tampak "kosong":
     *
     *   /mobilitas              → belum memilih apa pun   → periode terbaru
     *   /mobilitas?waktu_id=3   → memilih satu periode    → periode itu
     *   /mobilitas?waktu_id=    → memilih "Semua Periode" → null
     *
     * Pembedanya `has()`, bukan nilainya. Tanpa ini pilihan "Semua Periode"
     * jatuh ke cabang yang sama dengan kunjungan pertama, lalu halaman kembali
     * ke periode terbaru — filternya tampak tidak berfungsi.
     *
     * $bolehSemua HARUS true hanya untuk halaman yang angkanya berupa ARUS
     * (mobilitas: pindah/datang) atau yang tidak meringkas apa pun (ekspor
     * baris mentah). Untuk angka STOK — penduduk, KK, KTP, pendidikan, agama —
     * menjumlahkan antar semester menghitung orang yang sama berulang kali,
     * jadi bawaannya false: URL yang memaksa kosong tetap jatuh ke periode
     * terbaru, bukan menghasilkan angka yang tidak punya arti.
     */
    public function periodeTerpilih(Request $request, bool $bolehSemua = false, string $kunci = 'waktu_id'): ?int
    {
        if (! $request->has($kunci)) {
            return $this->getLatestWaktu()?->id;
        }

        $dipilih = $request->integer($kunci) ?: null;

        return $dipilih ?? ($bolehSemua ? null : $this->getLatestWaktu()?->id);
    }
}
