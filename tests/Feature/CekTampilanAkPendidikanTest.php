<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Verifikasi sementara: kolom baru Bukan Angkatan Kerja & Tidak Bekerja tampil dan datanya wajar. */
class CekTampilanAkPendidikanTest extends TestCase
{
    public function test_kolom_baru_tampil_dan_konsisten(): void
    {
        $res = $this->get('/sosial');
        $res->assertOk();
        $res->assertSee('Bukan Angkatan Kerja', false);
        $res->assertSee('Tidak Bekerja', false);

        // Ambil data mentah lewat query yang sama seperti controller supaya
        // tidak perlu memanggil controller kedua kali (request context beda).
        $waktuId = \App\Models\DimWaktu::orderByDesc('tahun')->orderByDesc('semester')->value('id');
        $aggr = function (string $jenis) use ($waktuId) {
            return \App\Models\DataAgregat::query()
                ->whereHas('kategori', fn ($q) => $q->where('jenis_indikator', $jenis)->aktif())
                ->where('waktu_id', $waktuId)
                ->with('kategori')
                ->get()
                ->groupBy('kategori.label')
                ->map(fn ($rows) => $rows->sum('jumlah'));
        };

        $jumlah   = $aggr('ak_pendidikan_jumlah_penduduk');
        $angkatan = $aggr('ak_pendidikan_angkatan_kerja');
        $bukanAk  = $aggr('ak_pendidikan_bukan_ak');
        $bekerja  = $aggr('ak_pendidikan_bekerja');
        $tidakBekerja = $aggr('ak_pendidikan_tidak_bekerja');

        foreach ([
            'TIDAK/BLM SEKOLAH', 'BELUM TAMAT SD/SEDERAJAT', 'TAMAT SD/SEDERAJAT',
            'SLTP/SEDERAJAT', 'SLTA/SEDERAJAT', 'DIPLOMA I/II',
            'AKADEMI/DIPLOMA III/S. MUDA', 'DIPLOMA IV/STRATA I', 'STRATA-II', 'STRATA-III',
        ] as $jenjang) {
            $j = $jumlah->get("Jumlah Penduduk ({$jenjang})", 0);
            $a = $angkatan->get("Angkatan Kerja ({$jenjang})", 0);
            $b = $bukanAk->get("Bukan Angkatan Kerja ({$jenjang})", 0);
            $k = $bekerja->get("Bekerja ({$jenjang})", 0);
            $t = $tidakBekerja->get("Tidak Bekerja ({$jenjang})", 0);

            fwrite(STDERR, sprintf(
                "\n  %-32s jml=%-8s ak=%-8s bukan_ak=%-8s bekerja=%-8s tidak_bekerja=%-8s",
                $jenjang, $j, $a, $b, $k, $t
            ));

            $this->assertEquals($j, $a + $b, "Jenjang {$jenjang}: jumlah != angkatan+bukan_ak");
            $this->assertEquals($a, $k + $t, "Jenjang {$jenjang}: angkatan != bekerja+tidak_bekerja");
        }
    }
}
