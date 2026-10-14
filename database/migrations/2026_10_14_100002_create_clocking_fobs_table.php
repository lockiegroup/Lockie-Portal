<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_fobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('clocking_employees')->nullOnDelete();
            $table->string('uid', 20)->unique(); // EM4100 10-digit, stored as string
            $table->boolean('is_active')->default(true);
            $table->dateTime('assigned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_fobs');
    }
};
