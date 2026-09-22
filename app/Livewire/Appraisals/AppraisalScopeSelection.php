<?php

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Services\AppraisalScopeService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AppraisalScopeSelection extends Component
{
    use AuthorizesRequests;

    public Appraisal $appraisal;

    /**
     * @var array<int, int> List of selected practice IDs
     */
    public array $selectedPracticeIds = [];

    public string $search = '';

    public ?int $levelFilter = null;

    public function mount(Appraisal $appraisal): void
    {
        $this->appraisal = $appraisal->load('project');

        $this->authorize('viewScope', $this->appraisal);

        $this->selectedPracticeIds = $this->appraisal->practices()
            ->pluck('practices.id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function togglePractice(int $practiceId): void
    {
        $this->authorize('updateScope', $this->appraisal);

        if (in_array($practiceId, $this->selectedPracticeIds, true)) {
            $this->selectedPracticeIds = array_values(
                array_filter($this->selectedPracticeIds, fn ($id) => $id !== $practiceId)
            );
        } else {
            $this->selectedPracticeIds[] = $practiceId;
        }
    }

    public function toggleArea(int $areaId): void
    {
        $this->authorize('updateScope', $this->appraisal);

        $areaPracticeIds = Practice::where('practice_area_id', $areaId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $allSelected = count($areaPracticeIds) > 0 && empty(array_diff($areaPracticeIds, $this->selectedPracticeIds));

        if ($allSelected) {
            $this->selectedPracticeIds = array_values(
                array_filter($this->selectedPracticeIds, fn ($id) => ! in_array($id, $areaPracticeIds, true))
            );
        } else {
            $this->selectedPracticeIds = array_values(
                array_unique(array_merge($this->selectedPracticeIds, $areaPracticeIds))
            );
        }
    }

    public function selectTargetLevel(int $level): void
    {
        $this->authorize('updateScope', $this->appraisal);

        $practiceIds = Practice::where('level', '<=', $level)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $this->selectedPracticeIds = array_values(
            array_unique(array_merge($this->selectedPracticeIds, $practiceIds))
        );
    }

    public function selectAll(): void
    {
        $this->authorize('updateScope', $this->appraisal);

        $this->selectedPracticeIds = Practice::pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function clearAll(): void
    {
        $this->authorize('updateScope', $this->appraisal);

        $this->selectedPracticeIds = [];
    }

    public function save(AppraisalScopeService $scopeService): void
    {
        $this->authorize('updateScope', $this->appraisal);

        if (empty($this->selectedPracticeIds)) {
            session()->flash('error', 'Debe seleccionar al menos una práctica CMMI para conformar el alcance.');
            return;
        }

        try {
            $scopeService->syncScope($this->appraisal, $this->selectedPracticeIds);
            session()->flash('message', 'Alcance CMMI guardado exitosamente ('.count($this->selectedPracticeIds).' prácticas en alcance).');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        $this->authorize('viewScope', $this->appraisal);

        $areas = PracticeArea::with(['practices' => function ($query) {
            $query->when($this->levelFilter !== null, fn ($q) => $q->where('level', $this->levelFilter))
                ->when($this->search !== '', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('code', 'like', '%'.$this->search.'%');
                    });
                })
                ->orderBy('level')
                ->orderBy('code');
        }])->get();

        $allPractices = Practice::all();
        $totalPractices = $allPractices->count();
        $selectedCount = count($this->selectedPracticeIds);

        $selectedPracticesCollection = $allPractices->whereIn('id', $this->selectedPracticeIds);
        $selectedAreasCount = $selectedPracticesCollection->pluck('practice_area_id')->unique()->count();
        $totalAreasCount = PracticeArea::count();

        $byLevel = [
            1 => $selectedPracticesCollection->where('level', 1)->count(),
            2 => $selectedPracticesCollection->where('level', 2)->count(),
            3 => $selectedPracticesCollection->where('level', 3)->count(),
        ];

        $canEdit = auth()->user()?->can('updateScope', $this->appraisal) ?? false;

        return view('livewire.appraisals.appraisal-scope-selection', [
            'areas' => $areas,
            'totalPractices' => $totalPractices,
            'selectedCount' => $selectedCount,
            'selectedAreasCount' => $selectedAreasCount,
            'totalAreasCount' => $totalAreasCount,
            'byLevel' => $byLevel,
            'canEdit' => $canEdit,
        ])->layout('layouts.app');
    }
}
