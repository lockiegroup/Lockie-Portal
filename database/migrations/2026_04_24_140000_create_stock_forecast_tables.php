<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_products', function (Blueprint $table) {
            $table->id();
            $table->string('guid', 50)->unique();
            $table->string('product_code', 100)->index();
            $table->string('product_name');
            $table->string('supplier_name')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('forecast_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('forecast_products')->cascadeOnDelete();
            $table->string('warehouse_code', 50);
            $table->string('warehouse_name');
            $table->decimal('qty_on_hand', 12, 4)->default(0);
            $table->decimal('qty_incoming', 12, 4)->default(0);
            $table->date('po_expected_date')->nullable();
            $table->decimal('qty_sold_90d', 12, 4)->default(0);
            $table->integer('lead_time_override')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->unique(['product_id', 'warehouse_code']);
        });

        Schema::create('supplier_settings', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name')->unique();
            $table->integer('lead_time_weeks')->default(4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_lines');
        Schema::dropIfExists('forecast_products');
        Schema::dropIfExists('supplier_settings');
    }
};
