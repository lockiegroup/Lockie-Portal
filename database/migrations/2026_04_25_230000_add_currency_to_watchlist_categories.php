<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->string('currency', 5)->default('£')->after('lead_time_days');
        });
    }

    public function down(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
