<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->integer('hue')->default(220);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('production_divisions')->insert([
            ['name' => 'Lockie',           'hue' => 22,  'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'JW',               'hue' => 130, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'A1',               'hue' => 200, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hammond & Harper', 'hue' => 270, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Warehousing',      'hue' => 170, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'General',          'hue' => 220, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('production_divisions');
    }
};
