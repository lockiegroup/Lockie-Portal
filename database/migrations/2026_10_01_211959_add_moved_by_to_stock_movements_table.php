<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->string('moved_by')->nullable()->after('notes');
            $table->string('action_type')->nullable()->after('moved_by'); // filled/cleared/moved-outside/moved-to-rack/outside-added/outside-removed
        });
    }
    public function down(): void {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['moved_by', 'action_type']);
        });
    }
};
