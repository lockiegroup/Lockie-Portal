<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('end_date');
        });

        // Seed sort_order from current alphabetical order
        $i = 0;
        DB::table('action_plans')->orderBy('name')->each(function ($plan) use (&$i) {
            DB::table('action_plans')->where('id', $plan->id)->update(['sort_order' => $i++]);
        });
    }

    public function down(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
