<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_time_days')->default(30)->after('position');
        });

        // Convert existing months → days
        DB::table('stock_watchlist_categories')->update([
            'lead_time_days' => DB::raw('lead_time_months * 30'),
        ]);

        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->dropColumn('lead_time_months');
        });
    }

    public function down(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('lead_time_months')->default(3)->after('position');
        });

        DB::table('stock_watchlist_categories')->update([
            'lead_time_months' => DB::raw('GREATEST(1, ROUND(lead_time_days / 30))'),
        ]);

        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->dropColumn('lead_time_days');
        });
    }
};
