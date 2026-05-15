<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_phones', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('reminder_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('account_code', 20);
            $table->string('name', 255)->nullable();
            $table->string('add1', 255)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('doc_no', 50)->nullable();
            $table->decimal('order_value', 10, 2)->nullable();
            $table->string('email', 255)->nullable();
            $table->decimal('env_sets', 8, 2)->nullable();
            $table->string('box_colour', 50)->nullable();
            $table->string('env_colour', 50)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('status', 50)->default('pending');
            $table->foreignId('called_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('called_date')->nullable();
            $table->text('call_notes')->nullable();
            $table->boolean('has_ordered')->default(false);
            $table->timestamps();

            $table->unique(['year', 'month', 'account_code']);
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_entries');
        Schema::dropIfExists('reminder_phones');
    }
};
