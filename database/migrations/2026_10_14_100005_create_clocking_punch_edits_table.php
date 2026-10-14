<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_punch_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('punch_id')->constrained('clocking_punches')->cascadeOnDelete();
            $table->foreignId('edited_by')->constrained('users');
            $table->json('before_state');
            $table->json('after_state');
            $table->text('reason');
            $table->dateTime('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clocking_punch_edits');
    }
};
