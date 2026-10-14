<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clocking_alert_log', function (Blueprint $table) {
            $table->string('alert_type', 20)->default('absent')->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('clocking_alert_log', function (Blueprint $table) {
            $table->dropColumn('alert_type');
        });
    }
};
