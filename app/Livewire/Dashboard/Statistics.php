<?php

namespace App\Livewire\Dashboard;

use App\Models\Appraisal;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Statistics extends Component
{
    use AuthorizesRequests;

    public ?int $appraisalId = null;

    public function mount(): void
    {
        $this->authorize('ver-readiness');

        $this->appraisalId = $this->visibleAppraisals()
            ->sortBy(fn (Appraisal $appraisal): int => $appraisal->isActivo() ? 0 : 1)
            ->first()?->id;
    }

    public function render(StatisticsService $statistics): View
    {
        $this->authorize('ver-readiness');

        $appraisal = $this->appraisalId !== null
            ? Appraisal::with('project')->find($this->appraisalId)
            : null;

        if ($appraisal instanceof Appraisal) {
            $this->authorize('view', $appraisal);
        }

        return view('livewire.dashboard.statistics', [
            'appraisals' => $this->visibleAppraisals(),
            'appraisal' => $appraisal,
            'stats' => $appraisal instanceof Appraisal ? $statistics->forAppraisal($appraisal) : null,
        ]);
    }

    /**
     * @return Collection<int, Appraisal>
     */
    private function visibleAppraisals(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return Appraisal::query()
            ->with('project')
            ->visibleFor($user)
            ->orderByDesc('id')
            ->get();
    }
}
