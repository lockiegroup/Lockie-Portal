<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_action_buckets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('key_action_groups')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('key_action_tasks', function (Blueprint $table) {
            $table->foreignId('bucket_id')->nullable()->after('assigned_to')
                  ->constrained('key_action_buckets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('key_action_tasks', function (Blueprint $table) {
            $table->dropForeign(['bucket_id']);
            $table->dropColumn('bucket_id');
        });
        Schema::dropIfExists('key_action_buckets');
    }
};
