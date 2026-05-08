<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collar_products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->nullable();
            $table->string('description');
            $table->string('reel_width')->nullable();
            $table->boolean('is_stock_line')->default(true);
            $table->decimal('cut_blank_stock', 10, 2)->default(0);
            $table->integer('cut_blank_moq')->nullable();
            $table->integer('cut_blank_reorder_level')->nullable();
            $table->integer('made_moq')->nullable();
            $table->integer('made_reorder_level')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        Schema::create('collar_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collar_product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['cut_blank', 'made']);
            $table->decimal('qty', 10, 2);
            $table->string('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('collar_works_orders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('period');
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('collar_works_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('works_order_id')->constrained('collar_works_orders')->cascadeOnDelete();
            $table->foreignId('collar_product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['cut_blank', 'made']);
            $table->integer('qty');
            $table->string('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collar_works_order_lines');
        Schema::dropIfExists('collar_works_orders');
        Schema::dropIfExists('collar_stock_adjustments');
        Schema::dropIfExists('collar_products');
    }
};
