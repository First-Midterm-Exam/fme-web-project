<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\Project;
use DomainException;
use Illuminate\Validation\ValidationException;

class AppraisalStateService
{
    /**
     * Validate creation of an appraisal on a project.
     *
     * @throws ValidationException
     */
    public function validateCreation(int $projectId): Project
    {
        $project = Project::findOrFail($projectId);

        if (! $project->isActive()) {
            throw ValidationException::withMessages([
                'projectId' => 'No se puede crear un appraisal sobre un proyecto cerrado.',
            ]);
        }

        return $project;
    }

    /**
     * Validate project immutability once an appraisal is active or closed.
     *
     * @throws ValidationException
     */
    public function validateProjectChange(Appraisal $appraisal, int $newProjectId): void
    {
        if ($appraisal->exists && ! $appraisal->isBorrador() && $appraisal->project_id !== $newProjectId) {
            throw ValidationException::withMessages([
                'projectId' => 'El proyecto no puede ser modificado una vez que el appraisal está activo o cerrado.',
            ]);
        }
    }

    /**
     * Transition an appraisal from borrador to activo.
     * Freezes the scope and automatically generates evaluation assessments.
     *
     * @throws DomainException
     */
    public function activate(Appraisal $appraisal, ?AppraisalScopeService $scopeService = null): void
    {
        if (! $appraisal->isBorrador()) {
            throw new DomainException('Solo se puede activar un appraisal que esté en estado borrador.');
        }

        if (! $appraisal->project->isActive()) {
            throw new DomainException('No se puede activar un appraisal cuyo proyecto esté cerrado.');
        }

        $scopeService = $scopeService ?? app(AppraisalScopeService::class);
        $scopeService->freezeScopeOnActivation($appraisal);

        $appraisal->update([
            'status' => Appraisal::STATUS_ACTIVO,
        ]);
    }

    /**
     * Transition an appraisal from activo to cerrado.
     *
     * @throws DomainException
     */
    public function close(Appraisal $appraisal): void
    {
        if (! $appraisal->isActivo()) {
            throw new DomainException('Solo se puede cerrar un appraisal que esté en estado activo.');
        }

        $appraisal->update([
            'status' => Appraisal::STATUS_CERRADO,
        ]);
    }
}
