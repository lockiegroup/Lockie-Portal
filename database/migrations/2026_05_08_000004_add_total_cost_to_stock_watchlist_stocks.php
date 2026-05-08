<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_watchlist_stocks', function (Blueprint $table) {
            $table->decimal('total_cost', 14, 4)->default(0)->after('qty_on_order');
        });
    }

    public function down(): void
    {
        Schema::table('stock_watchlist_stocks', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
};
