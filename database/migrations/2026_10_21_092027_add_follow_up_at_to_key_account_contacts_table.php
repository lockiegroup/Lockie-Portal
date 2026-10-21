<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('key_account_contacts', function (Blueprint $table) {
            $table->date('follow_up_at')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('key_account_contacts', function (Blueprint $table) {
            $table->dropColumn('follow_up_at');
        });
    }
};
