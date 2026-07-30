<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_job_run_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_job_run_id')->constrained('print_job_runs')->cascadeOnDelete();
            $table->unsignedInteger('packs_cumulative');
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_job_run_progress');
    }
};
