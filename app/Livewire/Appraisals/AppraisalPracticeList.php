<?php

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeEvaluation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AppraisalPracticeList extends Component
{
    use AuthorizesRequests;

    public Appraisal $appraisal;

    public ?int $areaFilter = null;

    public string $statusFilter = 'all';

    public ?int $selectedPracticeId = null;

    public bool $showDetailModal = false;

    /**
     * @var array<string, array{except: mixed}>
     */
    protected $queryString = [
        'areaFilter' => ['except' => null],
        'statusFilter' => ['except' => 'all'],
    ];

    public function mount(Appraisal $appraisal): void
    {
        $this->appraisal = $appraisal->load('project');

        $this->authorize('view', $this->appraisal);
    }

    public function resetFilters(): void
    {
        $this->areaFilter = null;
        $this->statusFilter = 'all';
    }

    public function openDetailModal(int $practiceId): void
    {
        $this->selectedPracticeId = $practiceId;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->selectedPracticeId = null;
        $this->showDetailModal = false;
    }

    public function getCompliancePercentage(?PracticeEvaluation $evaluation): string
    {
        if ($evaluation === null) {
            return '0%';
        }

        return $evaluation->compliancePercentage().'%';
    }

    public function render(): View
    {
        $this->authorize('view', $this->appraisal);

        $practicesQuery = $this->appraisal->practices()
            ->with([
                'practiceArea',
                'criteria' => fn ($q) => $q->orderBy('code'),
                'evaluations' => fn ($q) => $q->where('appraisal_id', $this->appraisal->id),
                'evaluations.criterionChecks',
            ])
            ->when($this->areaFilter, fn ($q) => $q->where('practices.practice_area_id', $this->areaFilter));

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === PracticeEvaluation::STATUS_NO_EVALUADA) {
                $practicesQuery->where(function ($sub): void {
                    $sub->whereHas('evaluations', function ($q): void {
                        $q->where('appraisal_id', $this->appraisal->id)
                            ->where('status', PracticeEvaluation::STATUS_NO_EVALUADA);
                    })->orWhereDoesntHave('evaluations', function ($q): void {
                        $q->where('appraisal_id', $this->appraisal->id);
                    });
                });
            } else {
                $practicesQuery->whereHas('evaluations', function ($q): void {
                    $q->where('appraisal_id', $this->appraisal->id)
                        ->where('status', $this->statusFilter);
                });
            }
        }

        $practices = $practicesQuery
            ->orderBy('practices.practice_area_id')
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        $groupedPractices = $practices->groupBy('practice_area_id');

        $availableAreas = PracticeArea::whereHas('practices', function ($q): void {
            $q->whereHas('appraisals', fn ($aq) => $aq->where('appraisals.id', $this->appraisal->id));
        })->orderBy('code')->get();

        $totalScopePractices = $this->appraisal->practices()->count();
        $totalScopeAreas = $this->appraisal->practices()->pluck('practices.practice_area_id')->unique()->count();
        $filteredCount = $practices->count();

        $selectedPractice = null;
        if ($this->selectedPracticeId !== null) {
            $selectedPractice = $practices->firstWhere('id', $this->selectedPracticeId)
                ?? Practice::with([
                    'practiceArea',
                    'criteria' => fn ($q) => $q->orderBy('code'),
                    'evaluations' => fn ($q) => $q->where('appraisal_id', $this->appraisal->id),
                    'evaluations.criterionChecks',
                ])->find($this->selectedPracticeId);
        }

        return view('livewire.appraisals.appraisal-practice-list', [
            'groupedPractices' => $groupedPractices,
            'availableAreas' => $availableAreas,
            'statuses' => PracticeEvaluation::statuses(),
            'totalScopePractices' => $totalScopePractices,
            'totalScopeAreas' => $totalScopeAreas,
            'filteredCount' => $filteredCount,
            'selectedPractice' => $selectedPractice,
        ])->layout('layouts.app');
    }
}
