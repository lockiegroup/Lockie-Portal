<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_account_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_account_id')->constrained()->cascadeOnDelete();
            $table->string('recipient', 200);
            $table->date('gifted_at');
            $table->string('description', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_account_gifts');
    }
};
