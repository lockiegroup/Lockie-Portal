<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->boolean('fully_complete')->nullable()->after('end_reason');
        });
    }

    public function down(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->dropColumn('fully_complete');
        });
    }
};
