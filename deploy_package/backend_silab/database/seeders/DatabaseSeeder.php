<?php

namespace Database\Seeders;

use App\Models\Petugas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Petugas::create([
            'nama' => 'Admin BRMP Biogen',
            'username' => 'admin',
            'email' => 'admin@brmp.go.id',
            'password' => Hash::make('Password123!'),
            'role' => 'admin',
            'wajib_ganti_password' => true,
        ]);
    }
}
