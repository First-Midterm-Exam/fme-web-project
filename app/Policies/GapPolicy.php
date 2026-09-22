<?php

namespace App\Policies;

use App\Models\Gap;
use App\Models\Rol;
use App\Models\User;

class GapPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Gap $gap): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        if ($gap->correctiveActions()->where('responsible_id', $user->id)->exists()) {
            return true;
        }

        $project = $gap->practiceEvaluation?->appraisal?->project;

        if (! $project) {
            return false;
        }

        return $project->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS);
    }

    public function update(User $user, Gap $gap): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)
            && $this->view($user, $gap);
    }

    public function delete(User $user, Gap $gap): bool
    {
        return $user->esAdministrador() && $this->view($user, $gap);
    }

    public function validate(User $user, Gap $gap): bool
    {
        if ($gap->status !== Gap::STATUS_RESUELTO) {
            return false;
        }

        if ($user->esAdministrador()) {
            return true;
        }

        if ($user->tieneRol(Rol::GESTOR_PROCESOS)) {
            $project = $gap->practiceEvaluation?->appraisal?->project;
            if (! $project) {
                return false;
            }

            return $user->projects()->where('projects.id', $project->id)->exists();
        }

        return false;
    }
}
