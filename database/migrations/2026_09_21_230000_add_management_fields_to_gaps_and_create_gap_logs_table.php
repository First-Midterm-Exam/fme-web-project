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
        Schema::table('gaps', function (Blueprint $table): void {
            $table->string('severity', 30)->default('Media')->after('description');
            $table->foreignId('assigned_to_id')->nullable()->after('severity')->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->after('assigned_to_id');

            $table->index(['severity', 'status']);
            $table->index('due_date');
        });

        Schema::create('gap_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gap_id')->constrained('gaps')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['gap_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gap_logs');

        Schema::table('gaps', function (Blueprint $table): void {
            $table->dropForeign(['assigned_to_id']);
            $table->dropColumn(['severity', 'assigned_to_id', 'due_date']);
        });
    }
};
