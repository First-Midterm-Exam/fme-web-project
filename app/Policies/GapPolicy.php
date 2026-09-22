<?php

namespace App\Policies;

use App\Models\Gap;
use App\Models\Rol;
use App\Models\User;

class GapPolicy
{
    /**
     * Determine whether the user can view the gaps list.
     * All 4 roles can access; query is scoped by project visibility.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific gap.
     * Inherited visibility from project:
     * Admin and Gestor de Procesos can view any gap.
     * Jefe de Proyecto and Colaborador only if assigned to the gap's project.
     */
    public function view(User $user, Gap $gap): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        $project = $gap->practiceEvaluation?->appraisal?->project;

        if (! $project) {
            return false;
        }

        return $project->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create a gap.
     * Only Administrador and Gestor de Procesos.
     */
    public function create(User $user): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS);
    }

    /**
     * Determine whether the user can update a gap.
     * Only Administrador and Gestor de Procesos, and only if visible to them.
     * Jefe de Proyecto and Colaborador receive false (403).
     */
    public function update(User $user, Gap $gap): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)
            && $this->view($user, $gap);
    }

    /**
     * Determine whether the user can delete a gap.
     * Only Administrador.
     */
    public function delete(User $user, Gap $gap): bool
    {
        return $user->esAdministrador() && $this->view($user, $gap);
    }
}
