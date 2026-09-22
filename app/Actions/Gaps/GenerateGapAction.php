<?php

namespace App\Actions\Gaps;

use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\Gap;
use App\Models\PracticeCriterion;
use App\Models\PracticeEvaluation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateGapAction
{
    public function fromUnmetCriterion(PracticeEvaluation $evaluation, PracticeCriterion $criterion, User $user): Gap
    {
        return DB::transaction(function () use ($evaluation, $criterion, $user): Gap {
            $existing = Gap::abiertos()
                ->where('practice_evaluation_id', $evaluation->id)
                ->where('practice_criterion_id', $criterion->id)
                ->first();

            if ($existing instanceof Gap) {
                return $existing;
            }

            return Gap::create([
                'code' => Gap::generateNextCode(),
                'practice_evaluation_id' => $evaluation->id,
                'practice_criterion_id' => $criterion->id,
                'title' => 'Criterio '.$criterion->code.' no cumplido',
                'description' => $criterion->description,
                'status' => Gap::STATUS_ABIERTO,
                'generated_by' => $user->id,
            ]);
        });
    }

    /**
     * @return Collection<int, Gap>
     */
    public function fromRejectedEvidence(Evidence $evidence, User $user): Collection
    {
        return DB::transaction(function () use ($evidence, $user): Collection {
            $evaluations = PracticeEvaluation::query()
                ->whereIn('practice_id', $evidence->practices()->pluck('practices.id'))
                ->whereHas('appraisal', function ($query) use ($evidence): void {
                    $query->where('project_id', $evidence->project_id)
                        ->where('status', Appraisal::STATUS_ACTIVO);
                })
                ->orderBy('id')
                ->get();

            return $evaluations->map(function (PracticeEvaluation $evaluation) use ($evidence, $user): Gap {
                $existing = Gap::abiertos()
                    ->where('practice_evaluation_id', $evaluation->id)
                    ->where('evidence_id', $evidence->id)
                    ->first();

                if ($existing instanceof Gap) {
                    return $existing;
                }

                return Gap::create([
                    'code' => Gap::generateNextCode(),
                    'practice_evaluation_id' => $evaluation->id,
                    'evidence_id' => $evidence->id,
                    'title' => 'Evidencia '.$evidence->code.' rechazada',
                    'description' => $evidence->verification_reason,
                    'status' => Gap::STATUS_ABIERTO,
                    'generated_by' => $user->id,
                ]);
            })->values();
        });
    }
}
