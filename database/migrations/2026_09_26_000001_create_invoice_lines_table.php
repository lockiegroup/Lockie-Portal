<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 50)->nullable()->index();
            $table->date('invoice_date')->index();
            $table->string('customer_code', 100)->nullable();
            $table->string('customer', 255)->nullable();
            $table->string('warehouse', 100)->nullable();
            $table->string('product_code', 100)->nullable();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->decimal('sub_total', 14, 4)->default(0);
            $table->string('status', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
