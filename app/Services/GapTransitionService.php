<?php

namespace App\Services;

use App\Models\Gap;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class GapTransitionService
{
    /**
     * @throws DomainException
     */
    public function transition(Gap $gap, string $newStatus, ?User $user = null, ?string $reason = null): void
    {
        if ($gap->status === $newStatus) {
            return;
        }

        if (! $gap->canTransitionTo($newStatus)) {
            throw new DomainException(
                "Transición de estado inválida: no se puede cambiar de '{$gap->status}' a '{$newStatus}' saltándose pasos del ciclo de vida."
            );
        }

        DB::transaction(function () use ($gap, $newStatus, $user, $reason): void {
            $oldStatus = $gap->status;
            $gap->update(['status' => $newStatus]);

            $gap->recordLog(
                user: $user,
                field: 'status',
                oldValue: $oldStatus,
                newValue: $newStatus,
                description: $reason ?? "Cambio de estado de '{$oldStatus}' a '{$newStatus}'"
            );
        });
    }

    /**
     * @param  array{severity?: string, assigned_to_id?: int|null, due_date?: string|null, status?: string, title?: string, description?: string|null}  $data
     *
     * @throws DomainException
     */
    public function updateGap(Gap $gap, array $data, ?User $user = null): void
    {
        DB::transaction(function () use ($gap, $data, $user): void {
            if (isset($data['severity']) && $data['severity'] !== $gap->severity) {
                $oldSeverity = $gap->severity;
                $gap->severity = $data['severity'];
                $gap->recordLog(
                    user: $user,
                    field: 'severity',
                    oldValue: $oldSeverity,
                    newValue: $data['severity'],
                    description: "Cambio de severidad de '{$oldSeverity}' a '{$data['severity']}'"
                );
            }

            if (array_key_exists('assigned_to_id', $data) && (int) $data['assigned_to_id'] !== (int) $gap->assigned_to_id) {
                $oldAssignedId = $gap->assigned_to_id;
                $oldUserName = $gap->assignedTo !== null ? $gap->assignedTo->name : 'Sin asignar';
                $newAssignedId = $data['assigned_to_id'] ? (int) $data['assigned_to_id'] : null;
                $newUser = $newAssignedId !== null ? User::find($newAssignedId) : null;
                $newUserName = $newUser !== null ? $newUser->name : 'Sin asignar';

                $gap->assigned_to_id = $newAssignedId;
                $gap->recordLog(
                    user: $user,
                    field: 'assigned_to_id',
                    oldValue: $oldAssignedId ? (string) $oldAssignedId : null,
                    newValue: $newAssignedId ? (string) $newAssignedId : null,
                    description: "Reasignación de '{$oldUserName}' a '{$newUserName}'"
                );
            }

            if (array_key_exists('due_date', $data)) {
                $oldDate = $gap->due_date ? Carbon::parse($gap->due_date)->format('Y-m-d') : null;
                $newDate = $data['due_date'] ? Carbon::parse($data['due_date'])->format('Y-m-d') : null;

                if ($oldDate !== $newDate) {
                    $gap->due_date = $newDate;
                    $gap->recordLog(
                        user: $user,
                        field: 'due_date',
                        oldValue: $oldDate,
                        newValue: $newDate,
                        description: 'Fecha límite modificada de '.($oldDate ?? 'sin fecha').' a '.($newDate ?? 'sin fecha')
                    );
                }
            }

            if (isset($data['title'])) {
                $gap->title = $data['title'];
            }
            if (array_key_exists('description', $data)) {
                $gap->description = $data['description'];
            }

            $gap->save();

            if (isset($data['status']) && $data['status'] !== $gap->status) {
                $this->transition($gap, $data['status'], $user);
            }
        });
    }
}
