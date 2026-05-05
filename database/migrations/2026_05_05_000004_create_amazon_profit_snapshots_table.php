<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amazon_profit_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('fulfillment_channel', ['FBM', 'FBA']);
            $table->string('product_type');
            $table->string('sku')->nullable();
            $table->decimal('gross_sales', 10, 2)->default(0);
            $table->decimal('returns', 10, 2)->default(0);
            $table->decimal('referral_fees_net', 10, 2)->default(0);
            $table->decimal('fba_fees_net', 10, 2)->default(0);
            $table->decimal('ad_spend_net', 10, 2)->default(0);
            $table->decimal('cogs', 10, 2)->default(0);
            $table->decimal('gross_profit', 10, 2)->default(0);
            $table->decimal('gross_margin_pct', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amazon_profit_snapshots');
    }
};
