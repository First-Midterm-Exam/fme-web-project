<?php

namespace App\Actions\Gaps;

use App\Models\CorrectiveAction;
use App\Models\Gap;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ValidateGapClosureAction
{
    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(Gap $gap, User $user, string $decision, ?string $reason = null): Gap
    {
        if (! $user->can('validate', $gap)) {
            throw new AuthorizationException('No tiene permisos para validar el cierre de este gap.');
        }

        $normalizedDecision = strtolower(trim($decision));
        $isApproval = in_array($normalizedDecision, ['approve', 'aprobar'], true);
        $isRejection = in_array($normalizedDecision, ['reject', 'rechazar'], true);

        if (! $isApproval && ! $isRejection) {
            throw ValidationException::withMessages([
                'decision' => 'La decisión de validación seleccionada no es válida.',
            ]);
        }

        if ($isRejection) {
            $trimmedReason = is_string($reason) ? trim($reason) : '';
            if ($trimmedReason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Debe ingresar un motivo de rechazo al rechazar la solución del gap.',
                ]);
            }
        }

        return DB::transaction(function () use ($gap, $user, $isApproval, $reason): Gap {
            /** @var Gap $lockedGap */
            $lockedGap = Gap::whereKey($gap->id)->lockForUpdate()->firstOrFail();

            if ($lockedGap->status !== Gap::STATUS_RESUELTO) {
                throw ValidationException::withMessages([
                    'gap' => 'El gap no se encuentra en estado Resuelto para ser validado.',
                ]);
            }

            $activeAction = $lockedGap->correctiveActions->first(fn (CorrectiveAction $action) => $action->status !== CorrectiveAction::STATUS_CERRADA);

            if ($isApproval) {
                $lockedGap->transitionTo(
                    Gap::STATUS_VERIFICADO,
                    $user,
                    'Solución verificada por Gestor de Procesos'
                );

                $lockedGap->transitionTo(
                    Gap::STATUS_CERRADO,
                    $user,
                    'Gap cerrado tras verificación exitosa'
                );

                $lockedGap->update([
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                ]);

                if ($activeAction) {
                    $activeAction->update([
                        'status' => CorrectiveAction::STATUS_CERRADA,
                    ]);
                    $activeAction->recordLog(
                        $user,
                        'status',
                        $activeAction->status,
                        CorrectiveAction::STATUS_CERRADA,
                        'Acción correctiva cerrada al aprobar el cierre del gap'
                    );
                }
            } else {
                $trimmedReason = trim((string) $reason);

                $lockedGap->transitionTo(
                    Gap::STATUS_EN_PROGRESO,
                    $user,
                    'Solución rechazada: '.$trimmedReason
                );

                if ($activeAction) {
                    $oldProgress = $activeAction->progress_percent;
                    $activeAction->update([
                        'progress_percent' => 90,
                        'status' => CorrectiveAction::STATUS_EN_PROGRESO,
                    ]);
                    $activeAction->recordLog(
                        $user,
                        'progress_percent',
                        (string) $oldProgress,
                        '90',
                        'Avance reajustado al 90% por rechazo de solución: '.$trimmedReason
                    );
                }
            }

            return $lockedGap->fresh(['closedBy', 'logs.user', 'correctiveActions.solutionEvidence']);
        });
    }
}
