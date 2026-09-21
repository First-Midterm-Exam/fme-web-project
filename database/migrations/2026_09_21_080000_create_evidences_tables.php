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
        Schema::create('evidence_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('evidences', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->foreignId('status_id')->constrained('evidence_statuses');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('evidence_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidences')->cascadeOnDelete();
            $table->integer('number')->default(1);
            $table->string('file_public_id');
            $table->string('file_resource_type');
            $table->string('file_format')->nullable();
            $table->string('file_original_name');
            $table->unsignedBigInteger('file_size');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->unique(['evidence_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_versions');
        Schema::dropIfExists('evidences');
        Schema::dropIfExists('evidence_statuses');
    }
};
