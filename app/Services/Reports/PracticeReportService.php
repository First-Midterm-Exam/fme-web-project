<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Appraisal;
use App\Models\PracticeEvaluation;
use Illuminate\Support\Collection;

class PracticeReportService
{
    /**
     * @return array{
     *     appraisal: Appraisal,
     *     evaluations: Collection<int, PracticeEvaluation>,
     *     generated_at: string
     * }
     */
    public function getData(Appraisal $appraisal): array
    {
        $evaluations = PracticeEvaluation::query()
            ->where('appraisal_id', $appraisal->id)
            ->with(['practice.practiceArea'])
            ->orderBy('id')
            ->get();

        return [
            'appraisal' => $appraisal->load('project'),
            'evaluations' => $evaluations,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
