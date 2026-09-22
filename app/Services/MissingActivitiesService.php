<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\CorrectiveAction;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\PracticeEvaluation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MissingActivitiesService
{
    public function getMissingActivities(Appraisal $appraisal): Collection
    {
        $items = collect();

        $evaluationIds = $appraisal->practiceEvaluations()->pluck('id');
        $gaps = Gap::whereIn('practice_evaluation_id', $evaluationIds)->get();

        foreach ($gaps as $gap) {
            if (in_array(strtolower($gap->status ?? ''), ['abierto', 'open', 'en_progreso', 'en progreso', 'pendiente'])) {
                $priorityWeight = match (strtolower($gap->severity ?? 'media')) {
                    'critica', 'crítica', 'alta', 'high' => 100,
                    'media', 'medium' => 80,
                    default => 60,
                };

                $dueDate = $gap->due_date ?? null;
                $isOverdue = $dueDate ? Carbon::parse($dueDate)->isPast() : false;

                $items->push([
                    'type' => 'Gap Abierto',
                    'category' => 'gap',
                    'title' => 'Gap: '.($gap->title ?? $gap->description ?? 'Gap #'.$gap->id),
                    'severity' => $gap->severity ?? 'Media',
                    'due_date' => $dueDate,
                    'is_overdue' => $isOverdue,
                    'priority_score' => $priorityWeight + ($isOverdue ? 50 : 0),
                    'action_url' => route('gaps.index').'?appraisal_id='.$appraisal->id,
                    'action_label' => 'Gestionar Gap',
                ]);
            }
            if (class_exists(CorrectiveAction::class)) {
                $actions = CorrectiveAction::where('gap_id', $gap->id)
                    ->whereNotIn('status', ['completada', 'cerrada', 'completed', 'closed'])
                    ->get();

                foreach ($actions as $action) {
                    $isOverdue = $action->due_date ? Carbon::parse($action->due_date)->isPast() : false;
                    $items->push([
                        'type' => 'Acción Correctiva',
                        'category' => 'action',
                        'title' => 'Acción: '.($action->description ?? 'Acción #'.$action->id),
                        'severity' => $isOverdue ? 'Crítica (Vencida)' : 'Media',
                        'due_date' => $action->due_date ?? null,
                        'is_overdue' => $isOverdue,
                        'priority_score' => $isOverdue ? 120 : 70,
                        'action_url' => route('gaps.index').'?appraisal_id='.$appraisal->id,
                        'action_label' => 'Ver Acción',
                    ]);
                }
            }
        }

        $evaluations = PracticeEvaluation::where('appraisal_id', $appraisal->id)
            ->with('practice')
            ->get();

        foreach ($evaluations as $eval) {
            $status = strtolower($eval->status ?? 'pendiente');
            if (in_array($status, ['no_cumple', 'no cumple', 'no evaluada', 'parcial', 'pendiente'])) {
                $items->push([
                    'type' => 'Práctica No Cumplida',
                    'category' => 'practice',
                    'title' => 'Práctica '.($eval->practice->code ?? '').': '.($eval->practice->name ?? 'Evaluación #'.$eval->id),
                    'severity' => in_array($status, ['no_cumple', 'no cumple']) ? 'Alta' : 'Media',
                    'due_date' => null,
                    'is_overdue' => false,
                    'priority_score' => in_array($status, ['no_cumple', 'no cumple']) ? 65 : 50,
                    'action_url' => route('appraisals.practices', $appraisal),
                    'action_label' => 'Evaluar Práctica',
                ]);
            }
        }

        $evidences = Evidence::where('project_id', $appraisal->project_id)
            ->whereIn('status_id', [EvidenceStatus::REGISTRADA, EvidenceStatus::OBSERVADA, EvidenceStatus::RECHAZADA])
            ->get();

        foreach ($evidences as $evidence) {
            $isRejected = (int) $evidence->status_id === EvidenceStatus::RECHAZADA;
            $items->push([
                'type' => 'Evidencia Pendiente',
                'category' => 'evidence',
                'title' => 'Evidencia: '.($evidence->name ?? 'Evidencia #'.$evidence->id),
                'severity' => $isRejected ? 'Alta (Rechazada)' : 'Baja',
                'due_date' => null,
                'is_overdue' => false,
                'priority_score' => $isRejected ? 55 : 30,
                'action_url' => route('evidencias.index').'?appraisal_id='.$appraisal->id,
                'action_label' => 'Verificar Evidencia',
            ]);
        }

        return $items->sortByDesc('priority_score')->values();
    }
}
