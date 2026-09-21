<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('practice_assessments', 'practice_evaluations');
        Schema::rename('criterion_assessments', 'criterion_checks');
        Schema::rename('appraisal_scope', 'appraisal_scopes');

        Schema::table('criterion_checks', function (Blueprint $table): void {
            $table->renameColumn('practice_assessment_id', 'practice_evaluation_id');
        });

        Schema::table('appraisal_scopes', function (Blueprint $table): void {
            $table->foreignId('practice_area_id')->nullable()->after('appraisal_id')
                ->constrained('practice_areas')->cascadeOnDelete();
            $table->string('descripcion')->nullable()->after('practice_id');
            $table->boolean('incluida')->default(true)->after('descripcion');
        });

        DB::table('appraisal_scopes')->update([
            'practice_area_id' => DB::raw('(select practice_area_id from practices where practices.id = appraisal_scopes.practice_id)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisal_scopes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('practice_area_id');
            $table->dropColumn(['descripcion', 'incluida']);
        });

        Schema::table('criterion_checks', function (Blueprint $table): void {
            $table->renameColumn('practice_evaluation_id', 'practice_assessment_id');
        });

        Schema::rename('appraisal_scopes', 'appraisal_scope');
        Schema::rename('criterion_checks', 'criterion_assessments');
        Schema::rename('practice_evaluations', 'practice_assessments');
    }
};
