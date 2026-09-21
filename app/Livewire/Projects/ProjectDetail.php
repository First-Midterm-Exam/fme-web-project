<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ProjectDetail extends Component
{
    use AuthorizesRequests;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    public function render(): View
    {
        $this->project->load(['users', 'appraisals']);

        return view('livewire.projects.project-detail', [
            'project' => $this->project,
        ])->layout('layouts.app');
    }
}
