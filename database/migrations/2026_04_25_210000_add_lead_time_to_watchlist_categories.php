<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('lead_time_months')->default(3)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->dropColumn('lead_time_months');
        });
    }
};
