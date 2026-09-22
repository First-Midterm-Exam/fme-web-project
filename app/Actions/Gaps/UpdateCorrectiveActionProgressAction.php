<?php

namespace App\Actions\Gaps;

use App\Actions\Evidences\RegisterEvidenceAction;
use App\Models\CorrectiveAction;
use App\Models\Evidence;
use App\Models\Gap;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCorrectiveActionProgressAction
{
    public function __construct(private readonly RegisterEvidenceAction $registerEvidenceAction) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        CorrectiveAction $action,
        User $user,
        int $progressPercent,
        ?UploadedFile $file = null,
        ?string $comment = null
    ): CorrectiveAction {
        if (! $user->can('update', $action)) {
            throw new AuthorizationException('No tiene permisos para actualizar esta acción correctiva.');
        }

        if ($progressPercent < 0 || $progressPercent > 100) {
            throw ValidationException::withMessages([
                'progress_percent' => 'El porcentaje de avance debe ser un valor entre 0 y 100.',
            ]);
        }

        $gap = $action->gap;
        $project = $gap->practiceEvaluation?->appraisal?->project;

        return DB::transaction(function () use ($action, $user, $progressPercent, $file, $comment, $gap, $project): CorrectiveAction {
            $lockedAction = CorrectiveAction::whereKey($action->id)->lockForUpdate()->firstOrFail();

            if ($file !== null && $project !== null) {
                $practiceIds = [];
                if ($gap->practiceEvaluation?->practice_id) {
                    $practiceIds[] = (int) $gap->practiceEvaluation->practice_id;
                }

                $evidence = $this->registerEvidenceAction->execute(
                    $project,
                    $user,
                    $file,
                    [
                        'name' => 'Evidencia de Solución - '.$gap->code,
                        'type' => Evidence::TYPE_INFORME,
                        'description' => 'Evidencia de solución para la acción correctiva del gap '.$gap->code,
                    ],
                    $practiceIds
                );

                $lockedAction->solution_evidence_id = $evidence->id;
            }

            if ($progressPercent === 100) {
                if (! $lockedAction->solution_evidence_id) {
                    throw ValidationException::withMessages([
                        'file' => 'Es obligatorio adjuntar una evidencia de solución al completar la acción correctiva al 100%.',
                    ]);
                }

                if ($gap->status !== Gap::STATUS_EN_PROGRESO) {
                    throw ValidationException::withMessages([
                        'gap' => "El gap '{$gap->code}' no se encuentra en estado 'En progreso' (estado actual: '{$gap->status}'), por lo que no se puede completar la acción correctiva.",
                    ]);
                }

                $gap->transitionTo(
                    Gap::STATUS_RESUELTO,
                    $user,
                    'Acción correctiva completada al 100%'
                );
            } elseif ($progressPercent > 0 && $progressPercent < 100) {
                if ($lockedAction->status === CorrectiveAction::STATUS_ABIERTA) {
                    $lockedAction->status = CorrectiveAction::STATUS_EN_PROGRESO;
                }
            }

            $oldProgress = $lockedAction->progress_percent;
            $lockedAction->progress_percent = $progressPercent;
            $lockedAction->save();

            $logDescription = $comment !== null && trim($comment) !== ''
                ? trim($comment)
                : "Actualización de avance de {$oldProgress}% a {$progressPercent}%";

            $lockedAction->recordLog(
                $user,
                'progress_percent',
                (string) $oldProgress,
                (string) $progressPercent,
                $logDescription
            );

            return $lockedAction->fresh(['solutionEvidence', 'logs.user']);
        });
    }
}
