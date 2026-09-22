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
        Schema::create('corrective_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gap_id')->constrained('gaps')->cascadeOnDelete();
            $table->text('description');
            $table->foreignId('responsible_id')->constrained('users')->cascadeOnDelete();
            $table->date('due_date');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('status', 50)->default('abierta');
            $table->timestamps();

            $table->index(['gap_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
    }
};
