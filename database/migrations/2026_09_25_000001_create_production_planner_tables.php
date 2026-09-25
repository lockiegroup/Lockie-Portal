<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('am_hours', 4, 1)->default(4.0);
            $table->decimal('pm_hours', 4, 1)->default(4.0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('production_machines', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name', 100);
            $table->string('division', 50)->default('General');
            $table->unsignedSmallInteger('hue')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('week_key', 10)->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        // Seed machines if table is empty
        if (DB::table('production_machines')->count() === 0) {
            $machines = [
                ['key'=>'lockiechurch',  'name'=>'Lockie Church',      'division'=>'Lockie',           'hue'=>10,  'sort_order'=>1],
                ['key'=>'inkjet',        'name'=>'Inkjet',              'division'=>'Lockie',           'hue'=>22,  'sort_order'=>2],
                ['key'=>'rolenco',       'name'=>'Rolenco',             'division'=>'Lockie',           'hue'=>34,  'sort_order'=>3],
                ['key'=>'wd38',          'name'=>'WD38',                'division'=>'Lockie',           'hue'=>58,  'sort_order'=>4],
                ['key'=>'jw',            'name'=>'JW',                  'division'=>'JW',               'hue'=>106, 'sort_order'=>5],
                ['key'=>'laser',         'name'=>'Laser',               'division'=>'JW',               'hue'=>130, 'sort_order'=>6],
                ['key'=>'coditherm',     'name'=>'Coditherm',           'division'=>'JW',               'hue'=>154, 'sort_order'=>7],
                ['key'=>'a1',            'name'=>'A1',                  'division'=>'A1',               'hue'=>178, 'sort_order'=>8],
                ['key'=>'auto1',         'name'=>'Auto 1',              'division'=>'A1',               'hue'=>202, 'sort_order'=>9],
                ['key'=>'auto2',         'name'=>'Auto 2',              'division'=>'A1',               'hue'=>226, 'sort_order'=>10],
                ['key'=>'auto3',         'name'=>'Auto 3',              'division'=>'A1',               'hue'=>250, 'sort_order'=>11],
                ['key'=>'baby',          'name'=>'Baby',                'division'=>'A1',               'hue'=>274, 'sort_order'=>12],
                ['key'=>'hammondharper', 'name'=>'Hammond & Harper',    'division'=>'Hammond & Harper', 'hue'=>298, 'sort_order'=>13],
                ['key'=>'collars',       'name'=>'Collars',             'division'=>'Hammond & Harper', 'hue'=>82,  'sort_order'=>14],
                ['key'=>'picking',       'name'=>'Picking',             'division'=>'Hammond & Harper', 'hue'=>322, 'sort_order'=>15],
                ['key'=>'warehousing',   'name'=>'Warehousing',         'division'=>'Warehousing',      'hue'=>346, 'sort_order'=>16],
                ['key'=>'general',       'name'=>'General',             'division'=>'General',          'hue'=>358, 'sort_order'=>17],
            ];
            DB::table('production_machines')->insert(
                array_map(fn($m) => array_merge($m, ['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]), $machines)
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plans');
        Schema::dropIfExists('production_machines');
        Schema::dropIfExists('production_operators');
    }
};
