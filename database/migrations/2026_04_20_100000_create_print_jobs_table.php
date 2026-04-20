<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('unleashed_guid');
            $table->unsignedSmallInteger('line_number')->default(1);
            $table->string('order_number');
            $table->string('customer_name');
            $table->string('product_code')->nullable();
            $table->string('product_description')->nullable();
            $table->text('line_comment')->nullable();
            $table->decimal('order_total', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);
            $table->unsignedInteger('order_quantity')->default(0);
            $table->unsignedInteger('quantity_completed')->default(0);
            $table->date('required_date')->nullable();
            $table->date('original_required_date')->nullable();
            $table->string('board')->default('unplanned');
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('unleashed_status')->default('Open');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['unleashed_guid', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
