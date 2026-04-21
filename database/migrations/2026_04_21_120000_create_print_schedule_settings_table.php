<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('print_schedule_settings')->insert([
            ['key' => 'working_days',      'value' => '[1,2,3,4]', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'work_start',        'value' => '08:00',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'work_end',          'value' => '16:30',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'break_minutes',     'value' => '30',        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'throughput_auto_1', 'value' => '350',       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'throughput_auto_2', 'value' => '350',       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'throughput_auto_3', 'value' => '350',       'created_at' => now(), 'updated_at' => now()],
            ['key' => 'throughput_baby',   'value' => '180',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('print_schedule_settings');
    }
};
