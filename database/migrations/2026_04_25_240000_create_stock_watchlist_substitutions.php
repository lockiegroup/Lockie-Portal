<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_watchlist_substitutions', function (Blueprint $table) {
            $table->id();
            $table->string('find', 100);
            $table->string('replace', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_watchlist_substitutions');
    }
};
