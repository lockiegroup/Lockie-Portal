<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            // JSON: {"mon":{"start":"09:00","end":"17:30"}, "tue":{...}, ...}
            // null = no fixed schedule (not scheduled for missed-clock-in alerts)
            $table->json('schedule')->nullable();
            // Per-employee break deduction override; null = use global setting
            $table->unsignedSmallInteger('break_override_minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_employees');
    }
};
