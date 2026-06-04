<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_plans', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('description');
        });

        Schema::table('action_plan_items', function (Blueprint $table) {
            $table->text('assigned_user_ids')->nullable()->after('title');
        });

        // Migrate existing single assigned_user_id into JSON array
        DB::table('action_plan_items')
            ->whereNotNull('assigned_user_id')
            ->orderBy('id')
            ->each(function ($item) {
                DB::table('action_plan_items')
                    ->where('id', $item->id)
                    ->update(['assigned_user_ids' => json_encode([$item->assigned_user_id])]);
            });

        Schema::table('action_plan_items', function (Blueprint $table) {
            $table->dropForeign(['assigned_user_id']);
            $table->dropColumn('assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('action_plan_items', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_user_id')->nullable()->after('title');
        });

        Schema::table('action_plan_items', function (Blueprint $table) {
            $table->dropColumn('assigned_user_ids');
        });

        Schema::table('action_plans', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });
    }
};
