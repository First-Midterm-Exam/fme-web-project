<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\Rol;
use App\Models\User;

class AppraisalPolicy
{
    /**
     * Determine whether the user can view the listing of appraisals.
     * All authenticated users can enter; the query is scoped by project visibility.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific appraisal.
     * An appraisal is visible iff its project is visible to the user.
     */
    public function view(User $user, Appraisal $appraisal): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $appraisal->project->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create appraisals.
     * Exclusive to Administrador.
     */
    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    /**
     * Determine whether the user can update an appraisal's details.
     * Exclusive to Administrador, and only while in borrador state.
     */
    public function update(User $user, Appraisal $appraisal): bool
    {
        return $user->esAdministrador()
            && $appraisal->isBorrador()
            && $this->view($user, $appraisal);
    }

    /**
     * Determine whether the user can change the appraisal's status.
     * Administrador and Gestor de Procesos, and only if not cerrado.
     */
    public function updateStatus(User $user, Appraisal $appraisal): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)
            && ! $appraisal->isCerrado()
            && $this->view($user, $appraisal);
    }

    /**
     * Determine whether the user can view the appraisal's scope.
     */
    public function viewScope(User $user, Appraisal $appraisal): bool
    {
        return (new AppraisalScopePolicy)->view($user, $appraisal);
    }

    /**
     * Determine whether the user can update the appraisal's scope.
     */
    public function updateScope(User $user, Appraisal $appraisal): bool
    {
        return (new AppraisalScopePolicy)->update($user, $appraisal);
    }
}
