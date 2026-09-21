<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Rol;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     *
     * All authenticated users can reach the listing; the scope filters what they see.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific project (RN-07).
     *
     * - Administrador and Gestor de Procesos: always.
     * - Jefe de Proyecto and Colaborador: only if assigned in project_user.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $project->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create projects.
     *
     * Only Administrador.
     */
    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update a project.
     *
     * Only Administrador, and only if the project is active.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->esAdministrador() && $project->isActive();
    }

    /**
     * Determine whether the user can manage project members.
     *
     * Only Administrador, and only if the project is active.
     */
    public function manageMembers(User $user, Project $project): bool
    {
        return $user->esAdministrador() && $project->isActive();
    }

    /**
     * Determine whether the user can close a project.
     *
     * Only Administrador, and only if the project is active.
     */
    public function close(User $user, Project $project): bool
    {
        return $user->esAdministrador() && $project->isActive();
    }
}
