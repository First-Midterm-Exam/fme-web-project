<?php

namespace App\Services;

use App\Models\CorrectiveAction;
use App\Models\Gap;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectiveActionService
{
    /**
     * @param  array{description: string, responsible_id: int, due_date: string}  $data
     *
     * @throws ValidationException
     */
    public function createForGap(Gap $gap, array $data, ?User $creator = null): CorrectiveAction
    {
        $hasActive = $gap->correctiveActions()
            ->where('status', '!=', CorrectiveAction::STATUS_CERRADA)
            ->where('progress_percent', '<', 100)
            ->exists();

        if ($hasActive) {
            throw ValidationException::withMessages([
                'gap' => 'No se puede crear una segunda acción correctiva mientras la primera del mismo gap siga sin cerrarse.',
            ]);
        }

        return DB::transaction(function () use ($gap, $data, $creator) {
            /** @var CorrectiveAction $action */
            $action = $gap->correctiveActions()->create([
                'description' => $data['description'],
                'responsible_id' => $data['responsible_id'],
                'due_date' => $data['due_date'],
                'progress_percent' => 0,
                'status' => CorrectiveAction::STATUS_ABIERTA,
            ]);

            if ($gap->status === Gap::STATUS_ABIERTO) {
                $gap->transitionTo(
                    Gap::STATUS_EN_PROGRESO,
                    $creator,
                    'Transición automática a En progreso al crear acción correctiva'
                );
            }

            return $action;
        });
    }
}
