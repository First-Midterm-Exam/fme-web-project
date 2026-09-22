<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeCriterion;
use App\Models\PracticeEvaluation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class TraceabilityService
{
    /**
     * @return Collection<int, array{practice: Practice, evaluation: PracticeEvaluation|null, status: string, compliance: int, evidences: int, verified_evidences: int, open_gaps: int<0, max>, active_actions: int<0, max>}>
     */
    public function summary(Appraisal $appraisal): Collection
    {
        return $appraisal->practices()
            ->with([
                'practiceArea',
                'criteria',
                'evaluations' => fn ($query) => $query->where('appraisal_id', $appraisal->id),
                'evaluations.criterionChecks',
                'evaluations.gaps.correctiveActions',
            ])
            ->withCount([
                'evidences as evidences_total' => fn ($query) => $query->where('evidences.project_id', $appraisal->project_id),
                'evidences as evidences_verified' => fn ($query) => $query
                    ->where('evidences.project_id', $appraisal->project_id)
                    ->where('evidences.status_id', EvidenceStatus::VERIFICADA),
            ])
            ->orderBy('practices.code')
            ->get()
            ->map(function (Practice $practice): array {
                $evaluation = $practice->evaluations->first();

                if ($evaluation instanceof PracticeEvaluation) {
                    $evaluation->setRelation('practice', $practice);
                }

                $gaps = $evaluation instanceof PracticeEvaluation ? $evaluation->gaps : (new Gap)->newCollection();

                return [
                    'practice' => $practice,
                    'evaluation' => $evaluation instanceof PracticeEvaluation ? $evaluation : null,
                    'status' => $evaluation instanceof PracticeEvaluation ? $evaluation->status : PracticeEvaluation::STATUS_NO_EVALUADA,
                    'compliance' => $evaluation instanceof PracticeEvaluation ? $evaluation->compliancePercentage() : 0,
                    'evidences' => (int) $practice->getAttribute('evidences_total'),
                    'verified_evidences' => (int) $practice->getAttribute('evidences_verified'),
                    'open_gaps' => $gaps->filter(fn (Gap $gap): bool => ! in_array($gap->status, [Gap::STATUS_VERIFICADO, Gap::STATUS_CERRADO], true))->count(),
                    'active_actions' => $gaps->sum(fn (Gap $gap): int => $gap->correctiveActions->filter->isActive()->count()),
                ];
            })
            ->values();
    }

    /**
     * @return array{practice: Practice, evaluation: PracticeEvaluation|null, criteria: Collection<int, array{criterion: PracticeCriterion, check: CriterionCheck|null, gaps: EloquentCollection<int, Gap>}>, evidences: Collection<int, array{evidence: Evidence, gaps: EloquentCollection<int, Gap>}>, other_gaps: EloquentCollection<int, Gap>, gaps_total: int, actions_total: int}
     */
    public function chain(Appraisal $appraisal, Practice $practice): array
    {
        $practice->loadMissing(['practiceArea', 'criteria']);

        $evaluation = PracticeEvaluation::query()
            ->where('appraisal_id', $appraisal->id)
            ->where('practice_id', $practice->id)
            ->with([
                'criterionChecks',
                'gaps' => fn ($query) => $query->orderBy('code'),
                'gaps.assignedTo',
                'gaps.correctiveActions.responsible',
                'gaps.correctiveActions.solutionEvidence',
            ])
            ->first();

        $checks = ($evaluation instanceof PracticeEvaluation ? $evaluation->criterionChecks : (new CriterionCheck)->newCollection())
            ->keyBy('practice_criterion_id');
        $gaps = $evaluation instanceof PracticeEvaluation ? $evaluation->gaps : (new Gap)->newCollection();

        $criteria = $practice->criteria
            ->where('estado', true)
            ->map(fn (PracticeCriterion $criterion): array => [
                'criterion' => $criterion,
                'check' => $checks->get($criterion->id),
                'gaps' => $gaps->where('practice_criterion_id', $criterion->id)->values(),
            ])
            ->values();

        $evidences = $practice->evidences()
            ->where('evidences.project_id', $appraisal->project_id)
            ->with(['status', 'currentVersion', 'verifiedBy'])
            ->orderBy('evidences.code')
            ->get()
            ->map(fn (Evidence $evidence): array => [
                'evidence' => $evidence,
                'gaps' => $gaps->where('evidence_id', $evidence->id)->values(),
            ])
            ->values();

        return [
            'practice' => $practice,
            'evaluation' => $evaluation,
            'criteria' => $criteria,
            'evidences' => $evidences,
            'other_gaps' => $gaps->whereNull('practice_criterion_id')->whereNull('evidence_id')->values(),
            'gaps_total' => $gaps->count(),
            'actions_total' => $gaps->sum(fn (Gap $gap): int => $gap->correctiveActions->count()),
        ];
    }
}
