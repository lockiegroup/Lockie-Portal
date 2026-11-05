<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_line_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_line_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_qty', 10, 2);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_line_breaks');
    }
};
