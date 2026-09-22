<?php

namespace App\Policies;

use App\Models\CorrectiveAction;
use App\Models\Gap;
use App\Models\Rol;
use App\Models\User;

class CorrectiveActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS);
    }

    public function view(User $user, CorrectiveAction $action): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        if ($user->id === $action->responsible_id) {
            return true;
        }

        $project = $action->gap->practiceEvaluation?->appraisal?->project;

        if (! $project) {
            return false;
        }

        return $project->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user, Gap $gap): bool
    {
        if (! $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return false;
        }

        return (new GapPolicy)->view($user, $gap);
    }

    public function update(User $user, CorrectiveAction $action): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $user->id === $action->responsible_id;
    }
}
