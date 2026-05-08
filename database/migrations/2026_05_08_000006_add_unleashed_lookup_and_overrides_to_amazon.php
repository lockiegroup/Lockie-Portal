<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amazon_settlement_lines', function (Blueprint $table) {
            $table->string('unleashed_order_no', 50)->nullable()->after('order_id');
        });

        Schema::create('amazon_order_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settlement_id');
            $table->string('amazon_order_id', 50);
            $table->decimal('amount_override', 10, 2);
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique(['settlement_id', 'amazon_order_id']);
            $table->foreign('settlement_id')->references('id')->on('amazon_settlements')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_order_overrides');
        Schema::table('amazon_settlement_lines', function (Blueprint $table) {
            $table->dropColumn('unleashed_order_no');
        });
    }
};
