<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_flow_entries', function (Blueprint $table) {
            $table->foreignId('category_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('cash_flow_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_flow_entries', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\CashFlowCategory::class);
            $table->dropColumn('category_id');
        });
    }
};
