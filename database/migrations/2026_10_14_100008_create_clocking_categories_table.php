<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clocking_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();
        });

        Schema::create('clocking_alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('clocking_categories')->cascadeOnDelete();
            $table->string('email', 200);
            $table->string('label', 100)->nullable();
            $table->timestamps();
        });

        Schema::table('clocking_employees', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('clocking_categories')->nullOnDelete();
        });

        Schema::create('clocking_alert_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('clocking_employees')->cascadeOnDelete();
            $table->date('alert_date');
            $table->dateTime('sent_at');
            $table->json('recipients');
            $table->unique(['employee_id', 'alert_date']);
        });
    }

    public function down(): void
    {
        Schema::table('clocking_employees', fn($t) => $t->dropForeign(['category_id']));
        Schema::table('clocking_employees', fn($t) => $t->dropColumn('category_id'));
        Schema::dropIfExists('clocking_alert_log');
        Schema::dropIfExists('clocking_alert_recipients');
        Schema::dropIfExists('clocking_categories');
    }
};
