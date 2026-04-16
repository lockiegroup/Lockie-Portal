<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@lockiegroup.com'], [
            'name'      => 'Admin User',
            'password'  => bcrypt('Admin1234!'),
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}
