<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->enum('pause_type', ['dinner', 'away', 'end_of_shift', 'breakdown'])
                ->nullable()
                ->after('pause_reason');
        });
    }

    public function down(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->dropColumn('pause_type');
        });
    }
};
