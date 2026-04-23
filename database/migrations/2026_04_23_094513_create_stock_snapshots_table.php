<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->decimal('total_value', 12, 2);
            $table->json('warehouse_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_snapshots');
    }
};
