<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DimWilayahSeeder::class,
            // Alias harus menyusul wilayah — ia mencari id berdasarkan nama kelurahan
            AliasWilayahSeeder::class,
            DimKategoriSeeder::class,
            KonfigurasiImportSeeder::class,
            KonfigurasiExportSeeder::class,
            MetadataSeeder::class,
            UserSeeder::class,
        ]);
    }
}
