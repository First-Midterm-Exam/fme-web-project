<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\PracticeAssessment;
use DomainException;
use Illuminate\Support\Facades\DB;

class AppraisalScopeService
{
    /**
     * Synchronize the CMMI practices included in an appraisal's scope.
     * Scope can only be modified while the appraisal is in 'borrador' state.
     *
     * @param  array<int, int>  $practiceIds
     *
     * @throws DomainException
     */
    public function syncScope(Appraisal $appraisal, array $practiceIds): void
    {
        if (! $appraisal->isBorrador()) {
            throw new DomainException('El alcance solo puede modificarse cuando el appraisal está en estado borrador.');
        }

        $appraisal->practices()->sync($practiceIds);
    }

    /**
     * Freeze the scope of an appraisal when it is activated.
     * Automatically generates evaluation records in 'No evaluada' status for each practice in scope.
     *
     * @throws DomainException
     */
    public function freezeScopeOnActivation(Appraisal $appraisal): void
    {
        if (! $appraisal->isBorrador()) {
            throw new DomainException('Solo se puede congelar el alcance de un appraisal en estado borrador.');
        }

        $practices = $appraisal->practices()->get();

        DB::transaction(function () use ($appraisal, $practices): void {
            foreach ($practices as $practice) {
                PracticeAssessment::firstOrCreate(
                    [
                        'appraisal_id' => $appraisal->id,
                        'practice_id' => $practice->id,
                    ],
                    [
                        'status' => PracticeAssessment::STATUS_NO_EVALUADA,
                    ]
                );
            }
        });
    }

    /**
     * Check if the appraisal scope is currently editable.
     */
    public function isScopeEditable(Appraisal $appraisal): bool
    {
        return $appraisal->isBorrador();
    }
}
