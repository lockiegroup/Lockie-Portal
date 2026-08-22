<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ab_tests', function (Blueprint $table) {
            $table->decimal('variant_a_ctr', 6, 2)->nullable()->after('variant_a_result');
            $table->decimal('variant_b_ctr', 6, 2)->nullable()->after('variant_b_result');
        });
    }

    public function down(): void
    {
        Schema::table('ab_tests', function (Blueprint $table) {
            $table->dropColumn(['variant_a_ctr', 'variant_b_ctr']);
        });
    }
};
