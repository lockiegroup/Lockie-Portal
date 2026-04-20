<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envelope_designs', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(0)->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('envelope_designs', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
