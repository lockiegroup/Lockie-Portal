<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 50)->unique();
            $table->string('name', 200);
            $table->enum('type', ['key', 'growth'])->default('key');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_accounts');
    }
};
