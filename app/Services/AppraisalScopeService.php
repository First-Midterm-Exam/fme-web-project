<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\PracticeEvaluation;
use DomainException;
use Illuminate\Support\Facades\DB;

class AppraisalScopeService
{
    /**
     * @param  array<int, int>  $practiceIds
     *
     * @throws DomainException
     */
    public function syncScope(Appraisal $appraisal, array $practiceIds): void
    {
        if (! $appraisal->isBorrador()) {
            throw new DomainException('El alcance solo puede modificarse cuando el appraisal está en estado borrador.');
        }

        $appraisal->practices()->sync(AppraisalScope::pivotFor($practiceIds));
    }

    /**
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
                PracticeEvaluation::firstOrCreate(
                    [
                        'appraisal_id' => $appraisal->id,
                        'practice_id' => $practice->id,
                    ],
                    [
                        'status' => PracticeEvaluation::STATUS_NO_EVALUADA,
                    ]
                );
            }
        });
    }

    public function isScopeEditable(Appraisal $appraisal): bool
    {
        return $appraisal->isBorrador();
    }
}
