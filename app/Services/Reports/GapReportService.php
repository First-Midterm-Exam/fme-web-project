<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Appraisal;
use App\Models\Gap;
use Illuminate\Support\Collection;

class GapReportService
{
    /**
     * @return array{
     *     appraisal: Appraisal,
     *     gaps: Collection<int, Gap>,
     *     generated_at: string
     * }
     */
    public function getData(Appraisal $appraisal): array
    {
        $evaluationIds = $appraisal->practiceEvaluations()->pluck('id');

        $gaps = Gap::query()
            ->whereIn('practice_evaluation_id', $evaluationIds)
            ->with(['practiceEvaluation.practice.practiceArea', 'assignedTo', 'generatedBy'])
            ->orderBy('code')
            ->get();

        return [
            'appraisal' => $appraisal->load('project'),
            'gaps' => $gaps,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
