<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits_lines', function (Blueprint $table) {
            $table->id();
            $table->string('credit_no', 50)->nullable();
            $table->date('credit_date')->nullable()->index();
            $table->string('customer_code', 100)->nullable()->index();
            $table->string('product_code', 100)->nullable()->index();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('sub_total', 14, 2)->default(0);
            $table->string('status', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits_lines');
    }
};
