<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('users')->where('email', 'sales.team@lockiegroup.com')->exists()) {
            DB::table('users')->insert([
                'name'       => 'Sales Team',
                'email'      => 'sales.team@lockiegroup.com',
                'password'   => bcrypt(\Illuminate\Support\Str::random(64)),
                'role'       => 'staff',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'sales.team@lockiegroup.com')->delete();
    }
};
