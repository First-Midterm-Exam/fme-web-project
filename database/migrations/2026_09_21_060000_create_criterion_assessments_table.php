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
        Schema::create('criterion_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('practice_assessment_id')->constrained('practice_assessments')->cascadeOnDelete();
            $table->foreignId('practice_criterion_id')->constrained('practice_criteria')->cascadeOnDelete();
            $table->string('status', 50)->default('Pendiente');
            $table->text('notes')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->unique(['practice_assessment_id', 'practice_criterion_id'], 'criterion_assessments_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterion_assessments');
    }
};
