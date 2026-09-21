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
        Schema::create('appraisal_scope', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['appraisal_id', 'practice_id']);
        });

        Schema::create('practice_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained('appraisals')->cascadeOnDelete();
            $table->foreignId('practice_id')->constrained('practices')->cascadeOnDelete();
            $table->string('status', 50)->default('No evaluada');
            $table->timestamps();

            $table->unique(['appraisal_id', 'practice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_assessments');
        Schema::dropIfExists('appraisal_scope');
    }
};
