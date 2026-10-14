<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_hash', 64)->unique()->nullable();
            // One-time 6-digit registration code
            $table->string('registration_code', 6)->nullable();
            $table->dateTime('registration_code_expires_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_devices');
    }
};
