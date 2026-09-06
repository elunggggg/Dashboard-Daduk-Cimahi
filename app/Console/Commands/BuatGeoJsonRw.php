<?php

namespace App\Console\Commands;

use App\Models\AliasWilayah;
use App\Models\DimWilayah;
use Illuminate\Console\Command;
use SimpleXMLElement;

/**
 * Konversi KML batas RW (docs/Batas Rukun Warga Kota Cimahi.kmz — hasil
 * export Google My Maps) jadi GeoJSON siap pakai Leaflet.
 *
 * Kenapa custom, bukan ogr2ogr: tool itu tidak terpasang di server ini.
 * Kenapa cuma satu folder ("...seamless_administrasi...") yang dibaca:
 * berkas sumbernya berantakan — berisi banyak folder draft/garis kerja
 * yang tumpang tindih (lihat catatan panjang di bawah). Folder itu
 * SATU-SATUNYA yang lengkap (312 poligon, mencakup ke-15 kelurahan) dan
 * berformat rapi ala BIG/BPS (WADMKC/WADMKD/dst), jadi dipakai sebagai
 * satu-satunya sumber kebenaran.
 *
 * Kode wilayah (KDEPUM) di berkas sumber TIDAK bisa dipercaya — ada yang
 * kosong (Pasirkaliki), salah ketik (Cibeber tertulis prefix "33.77"
 * padahal seharusnya "32.77"), dan Leuwigajah kehilangan satu digit
 * ("32.77.01.004" seharusnya "...1004"). Karena itu pencocokan wilayah
 * dilakukan lewat NAMA kelurahan (WADMKD) memakai kamus yang sama dengan
 * importer Excel (DimWilayah::kamusPencocokan()), bukan lewat kode.
 */
class BuatGeoJsonRw extends Command
{
    protected $signature = 'peta:buat-geojson-rw
        {--sumber= : Path KML sumber (default storage/app/geo-sumber/batas-rw-cimahi.kml)}
        {--folder=seamless : Potongan nama folder KML yang dipakai sebagai sumber poligon RW}';

    protected $description = 'Konversi KML batas RW jadi public/geojson/batas-rw-cimahi.geojson';

    public function handle(): int
    {
        $sumber = $this->option('sumber') ?: storage_path('app/geo-sumber/batas-rw-cimahi.kml');

        if (! file_exists($sumber)) {
            $this->error("Berkas KML tidak ditemukan: {$sumber}");

            return self::FAILURE;
        }

        $xml = simplexml_load_file($sumber);

        if ($xml === false) {
            $this->error('Berkas KML gagal diparsing (bukan XML valid).');

            return self::FAILURE;
        }

        $placemarks = [];
        $this->kumpulkanPlacemark($xml->Document ?? $xml, $placemarks, $this->option('folder'));

        $this->info(count($placemarks)." placemark ditemukan di folder yang cocok dengan '{$this->option('folder')}'.");

        $kamusWilayah = DimWilayah::kamusPencocokan();
        $namaWilayah  = DimWilayah::pluck('nama_kelurahan', 'id')->all();
        $kecamatanWilayah = DimWilayah::pluck('nama_kecamatan', 'id')->all();

        $features   = [];
        $takKetemu  = [];
        $tanpaRw    = 0;

        foreach ($placemarks as $pm) {
            $data = $this->extendedData($pm);

            $namaKelurahanMentah = $data['WADMKD'] ?? $data['DESA'] ?? null;

            if ($namaKelurahanMentah === null) {
                continue;
            }

            $namaNormal = AliasWilayah::normalkan($namaKelurahanMentah);
            $wilayahId  = $kamusWilayah[$namaNormal] ?? null;

            if ($wilayahId === null) {
                $takKetemu[$namaKelurahanMentah] = ($takKetemu[$namaKelurahanMentah] ?? 0) + 1;

                continue;
            }

            $rw = $this->normalkanRw($data['RW'] ?? null);

            if ($rw === null) {
                $tanpaRw++;

                continue;
            }

            $koordinat = $this->ambilKoordinat($pm);

            if ($koordinat === null) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'rw'         => $rw,
                    'kelurahan'  => $namaWilayah[$wilayahId],
                    'kecamatan'  => $kecamatanWilayah[$wilayahId],
                    'wilayah_id' => $wilayahId,
                ],
                'geometry' => [
                    'type'        => 'Polygon',
                    'coordinates' => [$koordinat],
                ],
            ];
        }

        if ($takKetemu !== []) {
            $this->warn('Nama kelurahan di KML yang TIDAK cocok ke master wilayah (dilewati):');
            foreach ($takKetemu as $nama => $jumlah) {
                $this->line("  - \"{$nama}\" ({$jumlah} poligon)");
            }
        }

        if ($tanpaRw > 0) {
            $this->warn("{$tanpaRw} poligon dilewati karena nomor RW tidak terbaca.");
        }

        $wilayahTerbaca = collect($features)->pluck('properties.wilayah_id')->unique()->count();
        $this->info("{$wilayahTerbaca} dari 15 kelurahan punya poligon RW. Total poligon RW: ".count($features));

        $geojson = [
            'type'     => 'FeatureCollection',
            'features' => $features,
        ];

        $tujuan = public_path('geojson/batas-rw-cimahi.geojson');
        file_put_contents($tujuan, json_encode($geojson, JSON_UNESCAPED_SLASHES));

        $this->info('Ditulis ke '.$tujuan.' ('.round(filesize($tujuan) / 1024).' KB).');

        return self::SUCCESS;
    }

    /** @param array<int, SimpleXMLElement> $out */
    private function kumpulkanPlacemark(SimpleXMLElement $node, array &$out, string $folderCocok): void
    {
        foreach ($node->children('http://www.opengis.net/kml/2.2') as $child) {
            $tag = $child->getName();

            if ($tag === 'Folder' || $tag === 'Document') {
                $nama = (string) $child->name;

                if (str_contains(mb_strtolower($nama), mb_strtolower($folderCocok))) {
                    foreach ($child->children('http://www.opengis.net/kml/2.2') as $pm) {
                        if ($pm->getName() === 'Placemark' && $pm->Polygon->count() > 0) {
                            $out[] = $pm;
                        }
                    }
                }

                $this->kumpulkanPlacemark($child, $out, $folderCocok);
            }
        }
    }

    /** @return array<string, string> */
    private function extendedData(SimpleXMLElement $placemark): array
    {
        $data = [];

        foreach ($placemark->ExtendedData->Data ?? [] as $d) {
            $nama = (string) $d->attributes()->name;
            $data[$nama] = (string) $d->value;
        }

        return $data;
    }

    /**
     * "RW-05" / "RW 13" / "08" → "RW 08". Null bila tidak ada angka sama sekali.
     */
    private function normalkanRw(?string $mentah): ?string
    {
        if ($mentah === null || trim($mentah) === '') {
            return null;
        }

        if (! preg_match('/(\d+)/', $mentah, $cocok)) {
            return null;
        }

        return 'RW '.str_pad($cocok[1], 2, '0', STR_PAD_LEFT);
    }

    /**
     * Ring luar poligon KML → array [lon, lat] GeoJSON.
     *
     * @return array<int, array{0: float, 1: float}>|null
     */
    private function ambilKoordinat(SimpleXMLElement $placemark): ?array
    {
        $teks = (string) ($placemark->Polygon->outerBoundaryIs->LinearRing->coordinates ?? '');
        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }

        $titik = [];

        foreach (preg_split('/\s+/', $teks) as $tripel) {
            $bagian = explode(',', $tripel);

            if (count($bagian) < 2) {
                continue;
            }

            $titik[] = [(float) $bagian[0], (float) $bagian[1]];
        }

        return count($titik) >= 4 ? $titik : null;
    }
}
