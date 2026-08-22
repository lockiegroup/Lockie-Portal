<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ab_tests', function (Blueprint $table) {
            $table->string('test_type', 50)->nullable()->change();
            $table->string('variant_a', 500)->nullable()->change();
            $table->string('variant_b', 500)->nullable()->change();
            $table->decimal('revenue', 10, 2)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('ab_tests', function (Blueprint $table) {
            $table->string('test_type', 50)->nullable(false)->change();
            $table->string('variant_a', 500)->nullable(false)->change();
            $table->string('variant_b', 500)->nullable(false)->change();
            $table->dropColumn('revenue');
        });
    }
};
