<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'petugas@daduk-cimahikota.go.id'],
            [
                'name'     => 'Petugas Disdukcapil',
                'password' => Hash::make('password'),
                'role'     => User::ROLE_PETUGAS,
            ]
        );
    }
}
