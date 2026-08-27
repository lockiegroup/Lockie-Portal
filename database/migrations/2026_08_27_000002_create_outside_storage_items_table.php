<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('outside_storage_items', function (Blueprint $table) {
            $table->id();
            $table->date('storage_date')->nullable();
            $table->string('colour')->nullable();
            $table->string('quantity')->nullable();
            $table->string('ref')->nullable();
            $table->smallInteger('year')->nullable();
            $table->date('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('outside_storage_items'); }
};
