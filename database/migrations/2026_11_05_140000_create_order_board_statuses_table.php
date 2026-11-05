<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_board_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label', 100);
            $table->string('color', 20)->default('#94a3b8');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed the default statuses so existing board_notes data still works
        DB::table('order_board_statuses')->insert([
            ['key' => 'proforma',       'label' => 'Proforma',          'color' => '#a78bfa', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'awaiting',       'label' => 'Awaiting Info',     'color' => '#fb923c', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'in_production',  'label' => 'In Production',     'color' => '#38bdf8', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ready',          'label' => 'Ready to Dispatch', 'color' => '#34d399', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'on_hold',        'label' => 'On Hold',           'color' => '#f87171', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_board_statuses');
    }
};
