<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('racking_items', function (Blueprint $table) {
            $table->id();
            $table->string('bay', 3);
            $table->string('division')->nullable();
            $table->text('description')->nullable();
            $table->string('pallet_ref')->nullable();
            $table->string('quantity')->nullable();
            $table->date('date_stored')->nullable();
            $table->boolean('is_unusable')->default(false);
            $table->boolean('for_outside_storage')->default(false);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('bay');
        });
    }
    public function down(): void { Schema::dropIfExists('racking_items'); }
};
