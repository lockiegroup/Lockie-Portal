<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_settlement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('amazon_settlements')->cascadeOnDelete();
            $table->string('transaction_type');
            $table->string('order_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('product_type')->nullable();
            $table->enum('fulfillment_channel', ['FBM', 'FBA'])->nullable();
            $table->decimal('amount_gross', 10, 4)->default(0);
            $table->decimal('amount_net', 10, 4)->default(0);
            $table->decimal('vat_amount', 10, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0.00);
            $table->string('account_code');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_settlement_lines');
    }
};
