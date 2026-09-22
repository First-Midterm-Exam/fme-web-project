<?php

namespace App\Repositories;

use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\PracticeEvaluation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class EvaluationRepository
{
    /**
     * @return array{evaluaciones: EloquentCollection<int, PracticeEvaluation>, evidencias: Collection<int, int>}
     */
    public function findEvaluacionesPorAppraisal(int $appraisalId): array
    {
        $appraisal = Appraisal::query()->findOrFail($appraisalId);

        $evaluaciones = PracticeEvaluation::query()
            ->where('appraisal_id', $appraisal->id)
            ->with(['criterionChecks', 'gaps'])
            ->get()
            ->keyBy('practice_id');

        $evidencias = Evidence::query()
            ->where('evidences.project_id', $appraisal->project_id)
            ->where('evidences.status_id', EvidenceStatus::VERIFICADA)
            ->join('evidence_practice', 'evidence_practice.evidence_id', '=', 'evidences.id')
            ->selectRaw('evidence_practice.practice_id, COUNT(DISTINCT evidences.id) as total')
            ->groupBy('evidence_practice.practice_id')
            ->pluck('total', 'evidence_practice.practice_id')
            ->map(fn ($total): int => (int) $total);

        return [
            'evaluaciones' => $evaluaciones,
            'evidencias' => $evidencias,
        ];
    }
}
