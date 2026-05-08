<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->json('shopify_skus')->nullable()->after('currency');
            $table->json('amazon_skus')->nullable()->after('shopify_skus');
        });
    }

    public function down(): void
    {
        Schema::table('stock_watchlist_categories', function (Blueprint $table) {
            $table->dropColumn(['shopify_skus', 'amazon_skus']);
        });
    }
};
