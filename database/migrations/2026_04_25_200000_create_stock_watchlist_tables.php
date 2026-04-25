<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_watchlist_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('stock_watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('stock_watchlist_categories')->cascadeOnDelete();
            $table->string('product_code', 100);
            $table->string('product_name')->nullable();
            $table->text('info')->nullable();
            $table->unsignedTinyInteger('lead_time_months')->default(1);
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('to_order_qty', 12, 2)->nullable();
            $table->boolean('discontinued')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'product_code']);
        });

        Schema::create('stock_watchlist_sales', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 100)->index();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('qty_sold', 12, 4)->default(0);
            $table->unique(['product_code', 'year', 'month']);
        });

        Schema::create('stock_watchlist_stock', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 100)->unique();
            $table->string('product_name')->nullable();
            $table->decimal('qty_on_hand', 12, 4)->default(0);
            $table->decimal('qty_allocated', 12, 4)->default(0);
            $table->decimal('qty_on_order', 12, 4)->default(0);
            $table->date('po_expected_date')->nullable();
            $table->timestamp('synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_watchlist_stock');
        Schema::dropIfExists('stock_watchlist_sales');
        Schema::dropIfExists('stock_watchlist_items');
        Schema::dropIfExists('stock_watchlist_categories');
    }
};
