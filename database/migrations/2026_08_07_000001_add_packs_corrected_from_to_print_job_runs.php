<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->unsignedInteger('packs_corrected_from')->nullable()->after('packs_produced');
        });
    }

    public function down(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->dropColumn('packs_corrected_from');
        });
    }
};
