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
        Schema::create('gaps', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('practice_evaluation_id')->constrained('practice_evaluations')->cascadeOnDelete();
            $table->foreignId('practice_criterion_id')->nullable()->constrained('practice_criteria')->nullOnDelete();
            $table->foreignId('evidence_id')->nullable()->constrained('evidences')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('Abierto');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['practice_evaluation_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaps');
    }
};
