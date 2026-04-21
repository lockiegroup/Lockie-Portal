<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('synced_at');
            $table->index('archived_at');
            $table->index('customer_name');
            $table->index('order_number');
            $table->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['customer_name']);
            $table->dropIndex(['order_number']);
            $table->dropIndex(['product_code']);
            $table->dropColumn('archived_at');
        });
    }
};
