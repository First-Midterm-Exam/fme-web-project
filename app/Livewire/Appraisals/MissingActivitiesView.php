<?php

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Services\MissingActivitiesService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class MissingActivitiesView extends Component
{
    use AuthorizesRequests;

    public Appraisal $appraisal;

    public string $filterType = 'all';

    public function mount(Appraisal $appraisal): void
    {
        $this->authorize('view', $appraisal);

        $this->appraisal = $appraisal;
    }

    public function render(MissingActivitiesService $service): View
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
