<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Gap;
use App\Models\CorrectiveAction;
use App\Models\PracticeEvaluation;
use App\Models\Evidence;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MissingActivitiesService
{
    /**
     * Obtiene y prioriza todos los pendientes de un appraisal.
     */
    public function getMissingActivities(Appraisal $appraisal): Collection
    {
        $items = collect();

        // 1. Acciones correctivas vencidas o pendientes asociadas a gaps del appraisal
        $evaluationIds = $appraisal->practiceEvaluations()->pluck('id');
        $gaps = Gap::whereIn('practice_evaluation_id', $evaluationIds)->get();

        foreach ($gaps as $gap) {
            // Gaps abiertos
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
                    'title' => 'Gap: ' . ($gap->title ?? $gap->description ?? 'Gap #' . $gap->id),
                    'severity' => $gap->severity ?? 'Media',
                    'due_date' => $dueDate,
                    'is_overdue' => $isOverdue,
                    'priority_score' => $priorityWeight + ($isOverdue ? 50 : 0),
                    'action_url' => route('gaps.index') . '?appraisal_id=' . $appraisal->id,
                    'action_label' => 'Gestionar Gap',
                ]);
            }
            // Acciones correctivas relacionadas
            if (class_exists(CorrectiveAction::class)) {
                $actions = CorrectiveAction::where('gap_id', $gap->id)
                    ->whereNotIn('status', ['completada', 'cerrada', 'completed', 'closed'])
                    ->get();

                foreach ($actions as $action) {
                    $isOverdue = $action->due_date ? Carbon::parse($action->due_date)->isPast() : false;
                    $items->push([
                        'type' => 'Acción Correctiva',
                        'category' => 'action',
                        'title' => 'Acción: ' . ($action->description ?? 'Acción #' . $action->id),
                        'severity' => $isOverdue ? 'Crítica (Vencida)' : 'Media',
                        'due_date' => $action->due_date ?? null,
                        'is_overdue' => $isOverdue,
                        'priority_score' => $isOverdue ? 120 : 70,
                        'action_url' => route('gaps.index') . '?appraisal_id=' . $appraisal->id,
                        'action_label' => 'Ver Acción',
                    ]);
                }
            }
        }

        // 2. Prácticas no cumplidas o pendientes de evaluación
        $evaluations = PracticeEvaluation::where('appraisal_id', $appraisal->id)
            ->with('practice')
            ->get();

        foreach ($evaluations as $eval) {
            $status = strtolower($eval->status ?? 'pendiente');
            if (in_array($status, ['no_cumple', 'pendiente', 'incompleto', 'no cumplido'])) {
                $items->push([
                    'type' => 'Práctica No Cumplida',
                    'category' => 'practice',
                    'title' => 'Práctica ' . ($eval->practice->code ?? '') . ': ' . ($eval->practice->name ?? 'Evaluación #' . $eval->id),
                    'severity' => $status === 'no_cumple' ? 'Alta' : 'Media',
                    'due_date' => null,
                    'is_overdue' => false,
                    'priority_score' => $status === 'no_cumple' ? 65 : 50,
                    'action_url' => route('appraisals.practices', $appraisal),
                    'action_label' => 'Evaluar Práctica',
                ]);
            }
        }

        // 3. Evidencias pendientes de verificación o aprobación
        $evidences = Evidence::where('appraisal_id', $appraisal->id)
            ->whereIn('status', ['borrador', 'pendiente', 'en_revision', 'rechazada'])
            ->get();

        foreach ($evidences as $evidence) {
            $isRejected = in_array(strtolower($evidence->status), ['rechazada', 'rejected']);
            $items->push([
                'type' => 'Evidencia Pendiente',
                'category' => 'evidence',
                'title' => 'Evidencia: ' . ($evidence->title ?? $evidence->name ?? 'Evidencia #' . $evidence->id),
                'severity' => $isRejected ? 'Alta (Rechazada)' : 'Baja',
                'due_date' => null,
                'is_overdue' => false,
                'priority_score' => $isRejected ? 55 : 30,
                'action_url' => route('evidences.index') . '?appraisal_id=' . $appraisal->id,
                'action_label' => 'Verificar Evidencia',
            ]);
        }

        // Ordenar por puntaje de urgencia descendente (CA-2)
        return $items->sortByDesc('priority_score')->values();
    }
}