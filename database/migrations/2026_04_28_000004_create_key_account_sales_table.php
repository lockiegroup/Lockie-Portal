<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_account_sales', function (Blueprint $table) {
            $table->id();
            $table->string('account_code');
            $table->unsignedSmallInteger('year');
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('q1', 12, 2)->default(0);
            $table->decimal('q2', 12, 2)->default(0);
            $table->decimal('q3', 12, 2)->default(0);
            $table->decimal('q4', 12, 2)->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['account_code', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_account_sales');
    }
};
