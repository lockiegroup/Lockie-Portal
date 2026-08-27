<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('racking_items', function (Blueprint $table) {
            $table->tinyInteger('slot_number')->default(1)->after('bay');
        });
        // Assign slot numbers 1-4 to existing rows per bay, ordered by sort_order
        $bays = DB::table('racking_items')->distinct()->pluck('bay');
        foreach ($bays as $bay) {
            $ids = DB::table('racking_items')->where('bay', $bay)->orderBy('sort_order')->orderBy('id')->pluck('id');
            foreach ($ids as $i => $id) {
                DB::table('racking_items')->where('id', $id)->update(['slot_number' => $i + 1]);
            }
        }
    }
    public function down(): void {
        Schema::table('racking_items', function (Blueprint $table) {
            $table->dropColumn('slot_number');
        });
    }
};
