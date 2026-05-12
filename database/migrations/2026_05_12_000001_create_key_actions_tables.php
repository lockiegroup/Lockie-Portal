<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_action_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('key_action_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('key_action_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->unique(['group_id', 'user_id']);
            $table->timestamps();
        });

        Schema::create('key_action_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('key_action_groups')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('label', ['none', 'yellow', 'red', 'green'])->default('none');
            $table->date('due_date')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('key_action_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('key_action_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_action_comments');
        Schema::dropIfExists('key_action_tasks');
        Schema::dropIfExists('key_action_group_members');
        Schema::dropIfExists('key_action_groups');
    }
};
