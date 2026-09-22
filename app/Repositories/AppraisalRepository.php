<?php

namespace App\Repositories;

use App\Models\Appraisal;

class AppraisalRepository
{
    public function findAppraisalConAlcance(int $appraisalId): Appraisal
    {
        return Appraisal::query()
            ->with([
                'project',
                'practices' => fn ($query) => $query
                    ->wherePivot('incluida', true)
                    ->orderBy('practices.code'),
                'practices.practiceArea',
                'practices.criteria' => fn ($query) => $query->where('estado', true),
            ])
            ->findOrFail($appraisalId);
    }
}
