<?php

declare(strict_types=1);

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Services\AppraisalReadinessService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AppraisalReadinessScore extends Component
{
    public Appraisal $appraisal;

    public function mount(Appraisal $appraisal): void
    {
        $this->appraisal = $appraisal;
    }

    public function render(AppraisalReadinessService $service): View
    {
        $readiness = $service->calculate($this->appraisal);

        return view('livewire.appraisals.appraisal-readiness-score', [
            'score' => $readiness['score'],
            'disclaimer' => $readiness['disclaimer'],
            'breakdown' => $readiness['breakdown'],
        ])->layout('layouts.app');
    }
}