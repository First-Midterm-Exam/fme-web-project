<?php

namespace App\Policies;

use App\Models\Appraisal;
use App\Models\Rol;
use App\Models\User;

class AppraisalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appraisal $appraisal): bool
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return true;
        }

        return $appraisal->project->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->esAdministrador();
    }

    public function update(User $user, Appraisal $appraisal): bool
    {
        return $user->esAdministrador()
            && $appraisal->isBorrador()
            && $this->view($user, $appraisal);
    }

    public function updateStatus(User $user, Appraisal $appraisal): bool
    {
        return $user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)
            && ! $appraisal->isCerrado()
            && $this->view($user, $appraisal);
    }

    public function evaluate(User $user, Appraisal $appraisal): bool
    {
        return $user->tieneRol(Rol::GESTOR_PROCESOS)
            && ! $appraisal->isCerrado()
            && $this->view($user, $appraisal);
    }

    public function viewScope(User $user, Appraisal $appraisal): bool
    {
        return (new AppraisalScopePolicy)->view($user, $appraisal);
    }

    public function updateScope(User $user, Appraisal $appraisal): bool
    {
        return (new AppraisalScopePolicy)->update($user, $appraisal);
    }

    public function exportReport(User $user, Appraisal $appraisal): bool
    {
        return $this->view($user, $appraisal);
    }
}
