<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->index('warehouse');
            $table->index(['warehouse', 'customer_code']);
            $table->index('sub_total');
        });
    }

    public function down(): void
    {
        Schema::table('sales_lines', function (Blueprint $table) {
            $table->dropIndex(['warehouse']);
            $table->dropIndex(['warehouse', 'customer_code']);
            $table->dropIndex(['sub_total']);
        });
    }
};
