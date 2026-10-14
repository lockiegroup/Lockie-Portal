<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_absent_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('clocking_employees')->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('created_at');

            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_absent_overrides');
    }
};
