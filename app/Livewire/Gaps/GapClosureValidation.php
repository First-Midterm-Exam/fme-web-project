<?php

namespace App\Livewire\Gaps;

use App\Actions\Gaps\ValidateGapClosureAction;
use App\Models\Gap;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class GapClosureValidation extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $severityFilter = 'all';

    public string $projectFilter = 'all';

    public bool $showDetailModal = false;

    public bool $showValidationModal = false;

    public ?Gap $selectedGap = null;

    public string $decision = 'approve';

    public string $rejectionReason = '';

    /**
     * @var array<string, array{except: string}>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'severityFilter' => ['except' => 'all'],
        'projectFilter' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        if (! $authUser->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            abort(403, 'No tiene autorización para acceder a la validación de cierre de gaps.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProjectFilter(): void
    {
        $this->resetPage();
    }

    public function openDetailModal(int $gapId): void
    {
        $gap = Gap::with([
            'practiceEvaluation.appraisal.project',
            'practiceEvaluation.practice.practiceArea',
            'practiceCriterion',
            'assignedTo',
            'generatedBy',
            'correctiveActions.solutionEvidence',
            'correctiveActions.responsible',
            'logs.user',
        ])->findOrFail($gapId);

        $this->authorize('view', $gap);

        $this->selectedGap = $gap;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedGap = null;
    }

    public function openValidationModal(int $gapId): void
    {
        $gap = Gap::with([
            'practiceEvaluation.appraisal.project',
            'correctiveActions.solutionEvidence',
            'correctiveActions.responsible',
        ])->findOrFail($gapId);

        $this->authorize('validate', $gap);

        $this->selectedGap = $gap;
        $this->decision = 'approve';
        $this->rejectionReason = '';
        $this->resetValidation();
        $this->showValidationModal = true;
    }

    public function closeValidationModal(): void
    {
        $this->showValidationModal = false;
        $this->selectedGap = null;
        $this->decision = 'approve';
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function submitValidation(ValidateGapClosureAction $action): void
    {
        if (! $this->selectedGap) {
            return;
        }

        $this->authorize('validate', $this->selectedGap);

        if ($this->decision === 'reject') {
            $this->validate([
                'rejectionReason' => ['required', 'string', 'min:5', 'max:1000'],
            ], [
                'rejectionReason.required' => 'Debe ingresar un motivo de rechazo al rechazar la solución del gap.',
                'rejectionReason.min' => 'El motivo de rechazo debe tener al menos 5 caracteres.',
                'rejectionReason.max' => 'El motivo de rechazo no puede superar los 1000 caracteres.',
            ]);
        }

        $code = $this->selectedGap->code;

        $action->execute(
            $this->selectedGap,
            auth()->user(),
            $this->decision,
            $this->rejectionReason
        );

        if ($this->decision === 'approve') {
            session()->flash('message', 'El gap '.$code.' fue validado y cerrado exitosamente.');
        } else {
            session()->flash('message', 'El gap '.$code.' fue rechazado y devuelto a estado En progreso.');
        }

        $this->closeValidationModal();
    }

    public function render(): View
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        if ($authUser->esAdministrador()) {
            $eligibleProjects = Project::orderBy('name')->get();
        } else {
            $eligibleProjects = $authUser->projects()->orderBy('name')->get();
        }

        $query = Gap::with([
            'practiceEvaluation.appraisal.project',
            'practiceEvaluation.practice',
            'assignedTo',
            'correctiveActions.solutionEvidence',
            'correctiveActions.responsible',
        ])->where('status', Gap::STATUS_RESUELTO);

        if (! $authUser->esAdministrador()) {
            $query->whereHas('practiceEvaluation.appraisal.project', function ($q) use ($authUser): void {
                $q->whereHas('users', function ($u) use ($authUser): void {
                    $u->where('users.id', $authUser->id);
                });
            });
        }

        if ($this->projectFilter !== 'all' && is_numeric($this->projectFilter)) {
            $query->whereHas('practiceEvaluation.appraisal', function ($q): void {
                $q->where('project_id', (int) $this->projectFilter);
            });
        }

        if ($this->severityFilter !== 'all') {
            $query->where('severity', $this->severityFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        $resolvedGaps = $query->orderBy('id', 'desc')->paginate(10);

        return view('livewire.gaps.gap-closure-validation', [
            'resolvedGaps' => $resolvedGaps,
            'eligibleProjects' => $eligibleProjects,
        ])->layout('layouts.app', ['header' => 'Validación de Cierre de Gaps']);
    }
}
