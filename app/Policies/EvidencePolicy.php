<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;

class EvidencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tieneRol(
            Rol::ADMINISTRADOR,
            Rol::GESTOR_PROCESOS,
            Rol::JEFE_PROYECTO,
            Rol::COLABORADOR
        );
    }

    public function view(User $user, Evidence $evidence): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        if ($user->tieneRol(Rol::JEFE_PROYECTO, Rol::COLABORADOR)) {
            return $user->projects()
                ->where('projects.id', $evidence->project_id)
                ->exists();
        }

        return false;
    }

    public function create(User $user, ?Project $project = null): bool
    {
        if (! $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO, Rol::COLABORADOR)) {
            return false;
        }

        if ($project !== null) {
            $hasActiveAppraisal = $project->appraisals()
                ->where('status', Appraisal::STATUS_ACTIVO)
                ->exists();

            if (! $hasActiveAppraisal) {
                return false;
            }

            if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
                return true;
            }

            return $user->projects()
                ->where('projects.id', $project->id)
                ->exists();
        }

        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return true;
    }

    public function download(User $user, Evidence $evidence): bool
    {
        return $this->view($user, $evidence);
    }
}
