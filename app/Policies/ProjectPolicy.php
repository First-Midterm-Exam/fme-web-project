<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Rol;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $project->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->esAdministrador() && $project->isActive();
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return $user->esAdministrador() && $project->isActive();
    }

    public function close(User $user, Project $project): bool
    {
        return $user->esAdministrador() && $project->isActive();
    }
}
