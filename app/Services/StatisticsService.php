<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\CorrectiveAction;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\PracticeEvaluation;

class StatisticsService
{
    public const PRACTICE_STATUSES = [
        PracticeEvaluation::STATUS_NO_EVALUADA,
        PracticeEvaluation::STATUS_NO_CUMPLE,
        PracticeEvaluation::STATUS_PARCIAL,
        PracticeEvaluation::STATUS_CUMPLE,
        PracticeEvaluation::STATUS_VERIFICADA,
    ];

    public const GAP_STATUSES = [
        Gap::STATUS_ABIERTO,
        Gap::STATUS_EN_PROGRESO,
        Gap::STATUS_RESUELTO,
        Gap::STATUS_VERIFICADO,
        Gap::STATUS_CERRADO,
    ];

    public const GAP_SEVERITIES = [
        Gap::SEVERITY_BAJA,
        Gap::SEVERITY_MEDIA,
        Gap::SEVERITY_ALTA,
        Gap::SEVERITY_CRITICA,
    ];

    public const ACTION_STATUSES = [
        CorrectiveAction::STATUS_ABIERTA => 'Abierta',
        CorrectiveAction::STATUS_EN_PROGRESO => 'En progreso',
        CorrectiveAction::STATUS_CERRADA => 'Cerrada',
    ];

    public function __construct(private readonly TraceabilityService $traceability) {}

    /**
     * @return array{practices: array<string, mixed>, evidences: array<string, mixed>, gaps: array<string, mixed>, actions: array<string, mixed>}
     */
    public function forAppraisal(Appraisal $appraisal): array
    {
        return [
            'practices' => $this->practices($appraisal),
            'evidences' => $this->evidences($appraisal),
            'gaps' => $this->gaps($appraisal),
            'actions' => $this->actions($appraisal),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function practices(Appraisal $appraisal): array
    {
        $rows = $this->traceability->summary($appraisal);

        $byArea = $rows
            ->groupBy(fn (array $row): string => $row['practice']->practiceArea->code)
            ->map(fn ($areaRows, string $code): array => [
                'code' => $code,
                'name' => $areaRows->first()['practice']->practiceArea->name,
                'total' => $areaRows->count(),
                'compliance' => (int) round($areaRows->avg('compliance')),
            ])
            ->sortKeys()
            ->values()
            ->all();

        return [
            'total' => $rows->count(),
            'average_compliance' => $rows->isEmpty() ? 0 : (int) round($rows->avg('compliance')),
            'by_status' => $this->countIn(self::PRACTICE_STATUSES, $rows->countBy('status')->all()),
            'by_area' => $byArea,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evidences(Appraisal $appraisal): array
    {
        $counts = Evidence::query()
            ->where('project_id', $appraisal->project_id)
            ->selectRaw('status_id, count(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        $statuses = EvidenceStatus::orderBy('id')->pluck('name', 'id');

        $byStatus = [];
        foreach ($statuses as $id => $name) {
            $byStatus[$name] = (int) ($counts[$id] ?? 0);
        }

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts[EvidenceStatus::REGISTRADA] ?? 0),
            'by_status' => $byStatus,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gaps(Appraisal $appraisal): array
    {
        $gaps = Gap::query()
            ->whereHas('practiceEvaluation', fn ($query) => $query->where('appraisal_id', $appraisal->id))
            ->get();

        $closed = $gaps->whereIn('status', [Gap::STATUS_VERIFICADO, Gap::STATUS_CERRADO])->count();

        return [
            'total' => $gaps->count(),
            'open' => $gaps->count() - $closed,
            'closed' => $closed,
            'overdue' => $gaps->filter(fn (Gap $gap): bool => $gap->isOverdue())->count(),
            'by_status' => $this->countIn(self::GAP_STATUSES, $gaps->countBy('status')->all()),
            'by_severity' => $this->countIn(self::GAP_SEVERITIES, $gaps->countBy('severity')->all()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function actions(Appraisal $appraisal): array
    {
        $actions = CorrectiveAction::query()
            ->whereHas('gap.practiceEvaluation', fn ($query) => $query->where('appraisal_id', $appraisal->id))
            ->get();

        $counts = $actions->countBy('status');

        $byStatus = [];
        foreach (self::ACTION_STATUSES as $status => $label) {
            $byStatus[$label] = (int) ($counts[$status] ?? 0);
        }

        return [
            'total' => $actions->count(),
            'active' => $actions->filter(fn (CorrectiveAction $action): bool => $action->isActive())->count(),
            'average_progress' => $actions->isEmpty() ? 0 : (int) round($actions->avg('progress_percent')),
            'overdue' => $actions->filter(fn (CorrectiveAction $action): bool => $action->isOverdue())->count(),
            'by_status' => $byStatus,
        ];
    }

    /**
     * @param  list<string>  $keys
     * @param  array<array-key, int>  $counts
     * @return array<string, int>
     */
    private function countIn(array $keys, array $counts): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = (int) ($counts[$key] ?? 0);
        }

        return $result;
    }
}
