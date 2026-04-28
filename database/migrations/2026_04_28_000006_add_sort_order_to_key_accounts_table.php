<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('key_accounts', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('type');
        });

        // Initialise sort_order from current name ordering so the list
        // doesn't shuffle on first load.
        $ids = DB::table('key_accounts')->orderBy('name')->pluck('id');
        foreach ($ids as $i => $id) {
            DB::table('key_accounts')->where('id', $id)->update(['sort_order' => $i]);
        }
    }

    public function down(): void
    {
        Schema::table('key_accounts', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
