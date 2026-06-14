<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('machine', 50);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('end_reason', ['pause', 'complete', 'handover'])->nullable();
            $table->integer('packs_produced')->nullable();
            $table->string('pause_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_job_runs');
    }
};
