<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_flow_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('type', ['income', 'expense']);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cash_flow_weekly', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->foreignId('category_id')->constrained('cash_flow_categories')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['forecast', 'actual'])->default('forecast');
            $table->timestamps();
            $table->unique(['week_start', 'category_id']);
        });

        Schema::create('cash_flow_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flow_weekly');
        Schema::dropIfExists('cash_flow_categories');
        Schema::dropIfExists('cash_flow_settings');
    }
};
