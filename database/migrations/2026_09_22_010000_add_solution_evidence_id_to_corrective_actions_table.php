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
        Schema::table('corrective_actions', function (Blueprint $table): void {
            $table->foreignId('solution_evidence_id')
                ->nullable()
                ->after('status')
                ->constrained('evidences')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corrective_actions', function (Blueprint $table): void {
            $table->dropForeign(['solution_evidence_id']);
            $table->dropColumn('solution_evidence_id');
        });
    }
};
