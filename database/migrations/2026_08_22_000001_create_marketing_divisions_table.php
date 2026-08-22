<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('marketing_divisions')->insert([
            ['name' => 'JW Products',       'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hammond and Harper', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lockie Church',      'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_divisions');
    }
};
