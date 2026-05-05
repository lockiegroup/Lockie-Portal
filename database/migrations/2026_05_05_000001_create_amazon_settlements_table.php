<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_id')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('deposit_amount', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->enum('status', ['pending', 'posted', 'reconciled'])->default('pending');
            $table->json('raw_data')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('xero_transaction_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_settlements');
    }
};
