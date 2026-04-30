<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_lines', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->nullable();
            $table->date('order_date')->nullable();
            $table->date('required_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('warehouse', 100)->nullable();
            $table->string('customer_code', 100)->nullable()->index();
            $table->string('customer', 255)->nullable();
            $table->string('customer_type', 100)->nullable();
            $table->string('product_code', 100)->nullable()->index();
            $table->string('product_group', 100)->nullable();
            $table->string('status', 50)->nullable();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('sub_total', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['customer_code', 'completed_date']);
            $table->index(['customer_code', 'order_date']);
            $table->index(['product_code', 'completed_date']);
            $table->index(['product_code', 'order_date']);
        });

        Schema::dropIfExists('key_account_sales');
        Schema::dropIfExists('stock_watchlist_sales');
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_lines');
    }
};
