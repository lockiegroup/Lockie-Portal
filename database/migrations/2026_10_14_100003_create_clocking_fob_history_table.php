<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_fob_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fob_id')->constrained('clocking_fobs')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('clocking_employees')->nullOnDelete();
            // assigned | unassigned | deactivated | reactivated
            $table->string('action', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_fob_history');
    }
};
