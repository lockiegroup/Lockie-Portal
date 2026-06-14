<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->integer('progress_packs')->nullable()->after('packs_produced');
            $table->timestamp('progress_at')->nullable()->after('progress_packs');
        });
    }

    public function down(): void
    {
        Schema::table('print_job_runs', function (Blueprint $table) {
            $table->dropColumn(['progress_packs', 'progress_at']);
        });
    }
};
