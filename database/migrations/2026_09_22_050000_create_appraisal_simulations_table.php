<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appraisal_simulations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('target_level');
            $table->decimal('score', 5, 2);
            $table->unsignedInteger('evaluated_practices')->default(0);
            $table->unsignedInteger('passed_practices')->default(0);
            $table->json('gaps_found')->nullable();
            $table->timestamps();

            $table->index(['appraisal_id', 'created_at']);
        });

        Schema::create('readiness_measurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('appraisal_simulation_id')->nullable()->constrained('appraisal_simulations')->cascadeOnDelete();
            $table->string('status', 30);
            $table->unsignedTinyInteger('level');
            $table->decimal('score', 5, 2);
            $table->json('breakdown')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readiness_measurements');
        Schema::dropIfExists('appraisal_simulations');
    }
};
