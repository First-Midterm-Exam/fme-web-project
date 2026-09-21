<?php

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeAssessment;
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

    /**
     * Initialize the component and authorize user access.
     */
    public function mount(Appraisal $appraisal): void
    {
        $this->appraisal = $appraisal->load('project');

        // Authorize view based on project visibility rules (inherited visibility)
        $this->authorize('view', $this->appraisal);
    }

    /**
     * Reset area and status filters.
     */
    public function resetFilters(): void
    {
        $this->areaFilter = null;
        $this->statusFilter = 'all';
    }

    /**
     * Open the detail modal for a specific practice.
     */
    public function openDetailModal(int $practiceId): void
    {
        $this->selectedPracticeId = $practiceId;
        $this->showDetailModal = true;
    }

    /**
     * Close the detail modal.
     */
    public function closeDetailModal(): void
    {
        $this->selectedPracticeId = null;
        $this->showDetailModal = false;
    }

    /**
     * Get the criteria completion percentage indicator for a practice assessment.
     *
     * In the current stage, criterion checks have not yet been evaluated (HU-09/HU-10).
     * If status is 'No evaluada', returns '0%'.
     * For other statuses without criterion evaluation data, indicates 'Pendiente de evaluación'.
     */
    public function getCompliancePercentage(string $status): string
    {
        if ($status === PracticeAssessment::STATUS_NO_EVALUADA) {
            return '0%';
        }

        return 'Pendiente de evaluación';
    }

    /**
     * Render the Livewire component view.
     */
    public function render(): View
    {
        $this->authorize('view', $this->appraisal);

        // Scope practices query eagerly loaded to prevent N+1 queries
        $practicesQuery = $this->appraisal->practices()
            ->with([
                'practiceArea',
                'criteria' => fn ($q) => $q->orderBy('code'),
                'assessments' => fn ($q) => $q->where('appraisal_id', $this->appraisal->id),
            ])
            ->when($this->areaFilter, fn ($q) => $q->where('practice_area_id', $this->areaFilter));

        // Filter by assessment status
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === PracticeAssessment::STATUS_NO_EVALUADA) {
                $practicesQuery->where(function ($sub): void {
                    $sub->whereHas('assessments', function ($q): void {
                        $q->where('appraisal_id', $this->appraisal->id)
                            ->where('status', PracticeAssessment::STATUS_NO_EVALUADA);
                    })->orWhereDoesntHave('assessments', function ($q): void {
                        $q->where('appraisal_id', $this->appraisal->id);
                    });
                });
            } else {
                $practicesQuery->whereHas('assessments', function ($q): void {
                    $q->where('appraisal_id', $this->appraisal->id)
                        ->where('status', $this->statusFilter);
                });
            }
        }

        $practices = $practicesQuery
            ->orderBy('practice_area_id')
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        // Group practices by Practice Area
        $groupedPractices = $practices->groupBy('practice_area_id');

        // Areas included in the appraisal's scope for the filter selector
        $availableAreas = PracticeArea::whereHas('practices', function ($q): void {
            $q->whereHas('appraisals', fn ($aq) => $aq->where('appraisals.id', $this->appraisal->id));
        })->orderBy('code')->get();

        // Scope metrics
        $totalScopePractices = $this->appraisal->practices()->count();
        $totalScopeAreas = $this->appraisal->practices()->pluck('practice_area_id')->unique()->count();
        $filteredCount = $practices->count();

        // Selected practice for modal detail view
        $selectedPractice = null;
        if ($this->selectedPracticeId !== null) {
            $selectedPractice = $practices->firstWhere('id', $this->selectedPracticeId)
                ?? Practice::with([
                    'practiceArea',
                    'criteria' => fn ($q) => $q->orderBy('code'),
                    'assessments' => fn ($q) => $q->where('appraisal_id', $this->appraisal->id),
                ])->find($this->selectedPracticeId);
        }

        return view('livewire.appraisals.appraisal-practice-list', [
            'groupedPractices' => $groupedPractices,
            'availableAreas' => $availableAreas,
            'statuses' => PracticeAssessment::statuses(),
            'totalScopePractices' => $totalScopePractices,
            'totalScopeAreas' => $totalScopeAreas,
            'filteredCount' => $filteredCount,
            'selectedPractice' => $selectedPractice,
        ])->layout('layouts.app');
    }
}
