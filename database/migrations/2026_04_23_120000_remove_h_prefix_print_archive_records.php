<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('print_jobs')
            ->whereNotNull('archived_at')
            ->where('product_code', 'like', 'H-%')
            ->delete();
    }

    public function down(): void
    {
        // Non-reversible data cleanup
    }
};
