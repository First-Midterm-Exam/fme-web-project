<?php

namespace App\Policies;

use App\Models\CorrectiveAction;
use App\Models\Gap;
use App\Models\Rol;
use App\Models\User;

class CorrectiveActionPolicy
{
    /**
     * Determine whether the user can view any corrective action.
     */
    public function viewAny(User $user): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS);
    }

    /**
     * Determine whether the user can view the specific corrective action.
     * Administrador and Gestor de Procesos always.
     * The assigned responsible user can ALWAYS view this specific action (HU-18 Criterion 4).
     * Project members can also view it.
     */
    public function view(User $user, CorrectiveAction $action): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        // Criterio 4: El responsable asignado puede ver esta acción aunque su rol no le dé acceso general a gaps
        if ($user->id === $action->responsible_id) {
            return true;
        }

        $project = $action->gap->practiceEvaluation?->appraisal?->project;

        if (! $project) {
            return false;
        }

        return $project->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create a corrective action for a gap.
     * Solo Gestor de Procesos y Administrador (HU-18).
     */
    public function create(User $user, Gap $gap): bool
    {
        if (! $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return false;
        }

        return (new GapPolicy)->view($user, $gap);
    }

    /**
     * Determine whether the user can update the corrective action.
     * Solo Administrador, Gestor de Procesos o el responsable asignado.
     */
    public function update(User $user, CorrectiveAction $action): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $user->id === $action->responsible_id;
    }
}
