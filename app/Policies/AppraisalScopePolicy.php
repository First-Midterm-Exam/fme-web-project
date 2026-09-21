<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\Rol;
use App\Models\User;

class AppraisalScopePolicy
{
    public function view(User $user, Appraisal $appraisal): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $appraisal->project->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Appraisal $appraisal): bool
    {
        if (! $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return false;
        }

        if (! $appraisal->isBorrador()) {
            return false;
        }

        return $this->view($user, $appraisal);
    }
}
