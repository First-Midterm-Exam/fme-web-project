<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Appraisal;
use App\Models\Evidence;
use Illuminate\Support\Collection;

class EvidenceReportService
{
    /**
     * @return array{
     *     appraisal: Appraisal,
     *     evidences: Collection<int, Evidence>,
     *     generated_at: string
     * }
     */
    public function getData(Appraisal $appraisal): array
    {
        $evidences = Evidence::query()
            ->where('project_id', $appraisal->project_id)
            ->with(['status', 'uploadedBy', 'verifiedBy'])
            ->orderBy('code')
            ->get();

        return [
            'appraisal' => $appraisal->load('project'),
            'evidences' => $evidences,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }
}
