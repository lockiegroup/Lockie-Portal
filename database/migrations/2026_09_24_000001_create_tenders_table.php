<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50);               // contracts_finder, find_a_tender
            $table->string('source_ref', 500)->unique(); // unique ID from source
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_location', 150)->nullable();
            $table->unsignedBigInteger('value_low')->nullable();
            $table->unsignedBigInteger('value_high')->nullable();
            $table->date('published_at')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('status', 30)->default('new'); // new, reviewed, shortlisted, applied, won, lost, ignored
            $table->string('ai_relevance', 30)->nullable(); // high, relevant, possible, not_relevant
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->text('ai_reasoning')->nullable();
            $table->json('ai_products')->nullable();
            $table->string('source_url', 1000)->nullable();
            $table->json('cpv_codes')->nullable();
            $table->string('incumbent_supplier')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('alerted_at')->nullable();
            $table->timestamps();

            $table->index(['ai_relevance', 'status']);
            $table->index('deadline_at');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
