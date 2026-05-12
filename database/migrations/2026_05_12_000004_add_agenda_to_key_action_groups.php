<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('key_action_groups', function (Blueprint $table) {
            $table->string('agenda_path')->nullable()->after('column_order');
            $table->string('agenda_original_name')->nullable()->after('agenda_path');
        });
    }

    public function down(): void
    {
        Schema::table('key_action_groups', function (Blueprint $table) {
            $table->dropColumn(['agenda_path', 'agenda_original_name']);
        });
    }
};
