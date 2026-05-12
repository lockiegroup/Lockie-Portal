<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('key_action_groups', function (Blueprint $table) {
            $table->json('column_order')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('key_action_groups', function (Blueprint $table) {
            $table->dropColumn('column_order');
        });
    }
};
