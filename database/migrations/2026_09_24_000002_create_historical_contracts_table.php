<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('buyer');
            $table->string('product_category');
            $table->text('description')->nullable();
            $table->string('incumbent_supplier')->nullable();
            $table->unsignedBigInteger('value_estimate')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->text('extension_options')->nullable();
            $table->string('status', 30)->default('monitoring'); // monitoring, replacement_found, closed
            $table->text('notes')->nullable();
            $table->foreignId('related_tender_id')->nullable()->constrained('tenders')->nullOnDelete();
            $table->timestamps();

            $table->index('contract_end');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_contracts');
    }
};
