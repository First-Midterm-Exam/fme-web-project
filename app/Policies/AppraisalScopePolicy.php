<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\Rol;
use App\Models\User;

class AppraisalScopePolicy
{
    /**
     * Determine whether the user can view the appraisal's CMMI scope.
     * Visibility delegates to project assignment rules (inherited visibility).
     */
    public function view(User $user, Appraisal $appraisal): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $appraisal->project->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can edit/modify the appraisal's CMMI scope.
     * Allowed only for Administrador and Gestor de Procesos,
     * only while the appraisal is in 'borrador' state, and only if visible.
     */
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
