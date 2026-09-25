<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];

    public function up(): void
    {
        if (!Schema::hasColumn('production_operators', 'schedule')) {
            Schema::table('production_operators', function (Blueprint $table) {
                $table->json('schedule')->nullable()->after('name');
            });
        }

        // Migrate existing am_hours / pm_hours into per-day schedule
        DB::table('production_operators')->orderBy('id')->each(function ($op) {
            if (!empty($op->schedule)) return; // already migrated
            $am = $op->am_hours ?? 4;
            $pm = $op->pm_hours ?? 4;
            $schedule = [];
            foreach (self::DAYS as $day) {
                $schedule[$day] = ['am' => (float) $am, 'pm' => (float) $pm];
            }
            DB::table('production_operators')->where('id', $op->id)->update([
                'schedule' => json_encode($schedule),
            ]);
        });

        if (Schema::hasColumn('production_operators', 'am_hours')) {
            Schema::table('production_operators', function (Blueprint $table) {
                $table->dropColumn(['am_hours', 'pm_hours']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('production_operators', function (Blueprint $table) {
            $table->decimal('am_hours', 4, 1)->default(4.0)->after('name');
            $table->decimal('pm_hours', 4, 1)->default(4.0)->after('am_hours');
        });

        DB::table('production_operators')->each(function ($op) {
            $schedule = json_decode($op->schedule, true) ?? [];
            $am = $schedule['mon']['am'] ?? 4;
            $pm = $schedule['mon']['pm'] ?? 4;
            DB::table('production_operators')->where('id', $op->id)->update([
                'am_hours' => $am,
                'pm_hours' => $pm,
            ]);
        });

        Schema::table('production_operators', function (Blueprint $table) {
            $table->dropColumn('schedule');
        });
    }
};
