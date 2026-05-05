<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->string('delivery_city', 100)->nullable()->after('line_comment');
            $table->string('delivery_postcode', 20)->nullable()->after('delivery_city');
            $table->text('delivery_address')->nullable()->after('delivery_postcode');
        });
    }

    public function down(): void
    {
        Schema::table('print_jobs', function (Blueprint $table) {
            $table->dropColumn(['delivery_city', 'delivery_postcode', 'delivery_address']);
        });
    }
};
