<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Appraisal;
use App\Services\AppraisalReadinessService;

class ReadinessReportService
{
    public function __construct(
        private readonly AppraisalReadinessService $readinessService
    ) {}

    /**
     * @return array{
     *     appraisal: Appraisal,
     *     score: float,
     *     disclaimer: string,
     *     breakdown: array<string, mixed>,
     *     generated_at: string
     * }
     */
    public function getData(Appraisal $appraisal): array
    {
        $result = $this->readinessService->calculate($appraisal);

        return [
            'appraisal' => $appraisal->load('project'),
            'score' => $result['score'],
            'disclaimer' => $result['disclaimer'],
            'breakdown' => $result['breakdown'],
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
