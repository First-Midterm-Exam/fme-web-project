<?php

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Services\MissingActivitiesService;
use Livewire\Component;

class MissingActivitiesView extends Component
{
    public Appraisal $appraisal;
    public string $filterType = 'all';

    public function mount(Appraisal $appraisal)
    {
        $this->appraisal = $appraisal;
    }

    public function render(MissingActivitiesService $service)
    {
        $allActivities = $service->getMissingActivities($this->appraisal);

        $activities = $this->filterType === 'all'
            ? $allActivities
            : $allActivities->where('category', $this->filterType);

        return view('livewire.appraisals.missing-activities-view', [
            'activities' => $activities,
            'totalCount' => $allActivities->count(),
            'gapsCount' => $allActivities->where('category', 'gap')->count(),
            'actionsCount' => $allActivities->where('category', 'action')->count(),
            'practicesCount' => $allActivities->where('category', 'practice')->count(),
            'evidencesCount' => $allActivities->where('category', 'evidence')->count(),
        ])->layout('layouts.app');
    }
}