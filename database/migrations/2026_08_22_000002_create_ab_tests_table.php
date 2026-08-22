<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_division_id')->constrained()->cascadeOnDelete();
            $table->string('campaign_name', 200);
            $table->date('sent_at');
            $table->string('test_type', 50); // subject_line, send_time, content, sender_name
            $table->string('variant_a', 500);
            $table->decimal('variant_a_result', 6, 2)->nullable(); // e.g. open rate %
            $table->string('variant_b', 500);
            $table->decimal('variant_b_result', 6, 2)->nullable();
            $table->string('winner', 20)->nullable(); // a, b, inconclusive
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_tests');
    }
};
