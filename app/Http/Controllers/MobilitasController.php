<?php

namespace App\Http\Controllers;

use App\Models\DataAgregat;
use App\Models\DimWaktu;
use App\Services\FilterWilayahService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MobilitasController extends Controller
{
    public function __construct(private FilterWilayahService $filter) {}

    public function __invoke(Request $request): View
    {
        $latestWaktu = $this->filter->getLatestWaktu();
        $waktuList   = $this->filter->getWaktuList();

        // Pindah & datang adalah ARUS — menjumlahkannya antar semester berarti
        // "total perpindahan sepanjang periode itu", angka yang punya arti.
        // Karena itu halaman ini satu-satunya yang membolehkan "Semua Periode".
        $waktuId = $this->filter->periodeTerpilih($request, bolehSemua: true);

        /*
         * Distribusi PER KELURAHAN (bukan per label — jenis_indikator ini
         * cuma punya SATU label 'Datang'/'Pindah' per baris, lihat
         * DimKategoriSeeder; mengelompokkan lewat label lama membuat chart
         * "Pendatang — Asal Wilayah" cuma menghasilkan SATU slice/kategori,
         * bukan distribusi sungguhan). Diurutkan terbanyak untuk bar chart.
         */
        $datangData = $this->perKelurahan('mobilitas_datang', $waktuId);
        $pindahData = $this->perKelurahan('mobilitas_pindah', $waktuId);

        $totalDatang = $datangData->sum();
        $totalPindah = $pindahData->sum();
        $saldo       = $totalDatang - $totalPindah;

        // Rasio Pindah-Datang = Pindah ÷ Datang × 100 (rasio arus, bukan rasio
        // terhadap populasi). Dihitung dari totalDatang/totalPindah yang sudah
        // ada di sini, BUKAN dari sheet RasioPindahDatang yang diimpor — nilai
        // sheet itu per kelurahan per periode tunggal, tidak sah dijumlahkan
        // saat "Semua Periode" dipilih, sedangkan rumus ini aman untuk semua
        // kombinasi filter periode yang tersedia di halaman ini.
        $rasioPindahDatang = $totalDatang > 0 ? round($totalPindah / $totalDatang * 100, 1) : 0;

        // Tren seluruh periode
        $trendDatang = $this->trendPerPeriode('mobilitas_datang', $waktuList);
        $trendPindah = $this->trendPerPeriode('mobilitas_pindah', $waktuList);

        $selectedWaktu = $waktuList->firstWhere('id', $waktuId);

        return view('mobilitas.index', compact(
            'datangData', 'pindahData',
            'totalDatang', 'totalPindah', 'saldo', 'rasioPindahDatang',
            'trendDatang', 'trendPindah',
            'waktuList', 'waktuId',
            'selectedWaktu', 'latestWaktu',
        ));
    }

    /** Total per kelurahan (nama_kelurahan => jumlah), diurutkan terbanyak. */
    private function perKelurahan(string $jenis, ?int $waktuId): Collection
    {
        return DataAgregat::query()
            ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
            ->when($waktuId, fn ($q) => $q->where('waktu_id', $waktuId))
            ->with('wilayah')
            ->get()
            ->groupBy(fn ($row) => $row->wilayah->nama_kelurahan)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortDesc();
    }

    private function trendPerPeriode(string $jenis, Collection $waktuList): Collection
    {
        return $waktuList->map(fn (DimWaktu $w) => [
            'label' => $w->label,
            'total' => DataAgregat::whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
                ->where('waktu_id', $w->id)
                ->sum('jumlah'),
        ]);
    }
}
