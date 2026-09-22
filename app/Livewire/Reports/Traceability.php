<?php

namespace App\Livewire\Reports;

use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\User;
use App\Services\TraceabilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class Traceability extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'appraisal')]
    public ?int $appraisalId = null;

    #[Url(as: 'practica')]
    public ?int $practiceId = null;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('ver-readiness');

        if ($this->appraisalId === null) {
            $this->appraisalId = $this->visibleAppraisals()
                ->sortBy(fn (Appraisal $appraisal): int => $appraisal->isActivo() ? 0 : 1)
                ->first()?->id;
        }
    }

    public function updatedAppraisalId(): void
    {
        $this->practiceId = null;
        $this->search = '';
    }

    public function selectPractice(int $practiceId): void
    {
        $this->practiceId = $practiceId;
    }

    public function render(TraceabilityService $traceability): View
    {
        $this->authorize('ver-readiness');

        $appraisal = $this->appraisalId !== null
            ? Appraisal::with('project')->find($this->appraisalId)
            : null;

        if ($appraisal instanceof Appraisal) {
            $this->authorize('view', $appraisal);
        }

        $summary = $appraisal instanceof Appraisal ? $traceability->summary($appraisal) : collect();

        if ($this->search !== '') {
            $term = Str::lower($this->search);
            $summary = $summary->filter(fn (array $row): bool => Str::contains(
                Str::lower($row['practice']->code.' '.$row['practice']->name),
                $term
            ))->values();
        }

        $practice = $appraisal instanceof Appraisal && $this->practiceId !== null
            ? $appraisal->practices()->where('practices.id', $this->practiceId)->first()
            : null;

        return view('livewire.reports.traceability', [
            'appraisals' => $this->visibleAppraisals(),
            'appraisal' => $appraisal,
            'summary' => $summary,
            'chain' => $appraisal instanceof Appraisal && $practice instanceof Practice
                ? $traceability->chain($appraisal, $practice)
                : null,
        ])->layout('layouts.app', ['header' => 'Trazabilidad de Prácticas']);
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
