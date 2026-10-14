<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('clocking_employees');
            $table->foreignId('fob_id')->nullable()->constrained('clocking_fobs')->nullOnDelete();
            // in | out
            $table->string('direction', 3);
            // Canonical punch time, stored UTC
            $table->dateTime('punched_at');
            $table->foreignId('device_id')->nullable()->constrained('clocking_devices')->nullOnDelete();
            // Client-generated UUID for idempotent sync
            $table->string('client_uuid', 36)->unique()->nullable();
            // kiosk | manual
            $table->string('source', 10)->default('kiosk');
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason')->nullable();
            // Soft-void (never hard-delete punches)
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('voided_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'punched_at']);
            $table->index('punched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_punches');
    }
};
