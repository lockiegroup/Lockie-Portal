<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_machines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('category', 100)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('retrain_months')->nullable(); // recommended re-train interval
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('training_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('employee_code', 50)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('training_machines')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('training_operators')->cascadeOnDelete();
            $table->date('trained_date');
            $table->date('expiry_date')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->string('pdf_original_name', 255)->nullable();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('training_planned', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('training_machines')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('training_operators')->cascadeOnDelete();
            $table->date('planned_date');
            $table->text('notes')->nullable();
            $table->boolean('completed')->default(false);
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_planned');
        Schema::dropIfExists('training_records');
        Schema::dropIfExists('training_operators');
        Schema::dropIfExists('training_machines');
    }
};
