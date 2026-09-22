<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Appraisal;
use App\Models\CorrectiveAction;
use App\Models\Gap;
use Illuminate\Support\Collection;

class CorrectiveActionReportService
{
    /**
     * @return array{
     *     appraisal: Appraisal,
     *     actions: Collection<int, CorrectiveAction>,
     *     generated_at: string
     * }
     */
    public function getData(Appraisal $appraisal): array
    {
        $evaluationIds = $appraisal->practiceEvaluations()->pluck('id');

        $gapIds = Gap::query()
            ->whereIn('practice_evaluation_id', $evaluationIds)
            ->pluck('id');

        $actions = CorrectiveAction::query()
            ->whereIn('gap_id', $gapIds)
            ->with(['gap.practiceEvaluation.practice', 'responsible'])
            ->orderBy('id')
            ->get();

        return [
            'appraisal' => $appraisal->load('project'),
            'actions' => $actions,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
