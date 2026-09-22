<?php

namespace App\Livewire\Gaps;

use App\Models\Appraisal;
use App\Models\Gap;
use App\Models\Rol;
use App\Models\User;
use App\Services\GapTransitionService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GapManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?Appraisal $appraisal = null;

    public string $search = '';

    public string $severityFilter = 'all';

    public string $statusFilter = 'all';

    public bool $overdueOnly = false;

    public bool $showEditModal = false;

    public ?int $editingGapId = null;

    public string $code = '';

    public string $title = '';

    public string $description = '';

    public string $severity = Gap::SEVERITY_MEDIA;

    public ?int $assignedToId = null;

    public string $dueDate = '';

    public string $status = Gap::STATUS_ABIERTO;

    public string $changeReason = '';

    public bool $showDetailModal = false;

    public ?int $viewingGapId = null;

    /**
     * @var array<string, array{except: string|bool}>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'severityFilter' => ['except' => 'all'],
        'statusFilter' => ['except' => 'all'],
        'overdueOnly' => ['except' => false],
    ];

    public function mount(?Appraisal $appraisal = null): void
    {
        $this->authorize('viewAny', Gap::class);

        if ($appraisal && $appraisal->exists) {
            $this->authorize('view', $appraisal);
            $this->appraisal = $appraisal->load('project');
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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingOverdueOnly(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $gapId): void
    {
        $gap = Gap::with('practiceEvaluation.appraisal.project')->findOrFail($gapId);
        $this->authorize('update', $gap);

        $this->editingGapId = $gap->id;
        $this->code = $gap->code;
        $this->title = $gap->title;
        $this->description = $gap->description ?? '';
        $this->severity = $gap->severity ?? Gap::SEVERITY_MEDIA;
        $this->assignedToId = $gap->assigned_to_id;
        $this->dueDate = $gap->due_date ? Carbon::parse($gap->due_date)->format('Y-m-d') : '';
        $this->status = $gap->status;
        $this->changeReason = '';

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingGapId = null;
        $this->resetValidation();
    }

    public function openDetail(int $gapId): void
    {
        $gap = Gap::with('practiceEvaluation.appraisal.project')->findOrFail($gapId);
        $this->authorize('view', $gap);

        $this->viewingGapId = $gap->id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingGapId = null;
    }

    public function save(GapTransitionService $transitionService): void
    {
        if (! $this->editingGapId) {
            return;
        }

        $gap = Gap::with('practiceEvaluation.appraisal.project')->findOrFail($this->editingGapId);
        $this->authorize('update', $gap);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['required', Rule::in([
                Gap::SEVERITY_BAJA,
                Gap::SEVERITY_MEDIA,
                Gap::SEVERITY_ALTA,
                Gap::SEVERITY_CRITICA,
            ])],
            'assignedToId' => ['nullable', 'exists:users,id'],
            'dueDate' => ['nullable', 'date'],
            'status' => ['required', Rule::in([
                Gap::STATUS_ABIERTO,
                Gap::STATUS_EN_PROGRESO,
                Gap::STATUS_RESUELTO,
                Gap::STATUS_VERIFICADO,
            ])],
        ], $this->messages());

        if ($this->status !== $gap->status && ! $gap->canTransitionTo($this->status)) {
            $this->addError(
                'status',
                'No se puede cambiar el estado del gap saltándose pasos del ciclo de vida. Secuencia: Abierto → En progreso → Resuelto → Verificado.'
            );

            return;
        }

        try {
            /** @var User $authUser */
            $authUser = auth()->user();

            $transitionService->updateGap($gap, [
                'title' => $this->title,
                'description' => $this->description,
                'severity' => $this->severity,
                'assigned_to_id' => $this->assignedToId ? (int) $this->assignedToId : null,
                'due_date' => $this->dueDate !== '' ? $this->dueDate : null,
                'status' => $this->status,
            ], $authUser);

            session()->flash('message', "El gap {$gap->code} ha sido actualizado con éxito.");
            $this->closeEditModal();
        } catch (DomainException $e) {
            $this->addError('status', $e->getMessage());
        }
    }

    public function render(): View
    {
        $this->authorize('viewAny', Gap::class);

        /** @var User $authUser */
        $authUser = auth()->user();

        $query = Gap::query()
            ->with([
                'practiceEvaluation.appraisal.project',
                'practiceEvaluation.practice',
                'practiceCriterion',
                'evidence',
                'assignedTo',
            ])
            ->visibleFor($authUser)
            ->when($this->appraisal, function ($q) {
                $q->whereHas('practiceEvaluation', fn ($pe) => $pe->where('appraisal_id', $this->appraisal->id));
            })
            ->when($this->severityFilter !== 'all', fn ($q) => $q->where('severity', $this->severityFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->overdueOnly, function ($q) {
                $q->where('due_date', '<', now()->toDateString())
                    ->whereNotIn('status', [Gap::STATUS_RESUELTO, Gap::STATUS_VERIFICADO, Gap::STATUS_CERRADO]);
            })
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('title', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHas('practiceEvaluation.practice', function ($pq) {
                            $pq->where('code', 'like', '%'.$this->search.'%')
                                ->orWhere('name', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderByRaw('CASE WHEN due_date IS NOT NULL AND due_date < ? AND status NOT IN (?, ?, ?) THEN 0 ELSE 1 END', [
                now()->toDateString(),
                Gap::STATUS_RESUELTO,
                Gap::STATUS_VERIFICADO,
                Gap::STATUS_CERRADO,
            ])
            ->orderByDesc('id');

        $gaps = $query->paginate(15);

        $baseCountQuery = Gap::query()->visibleFor($authUser)
            ->when($this->appraisal, function ($q) {
                $q->whereHas('practiceEvaluation', fn ($pe) => $pe->where('appraisal_id', $this->appraisal->id));
            });

        $counts = [
            'total' => (clone $baseCountQuery)->count(),
            'abiertos' => (clone $baseCountQuery)->where('status', Gap::STATUS_ABIERTO)->count(),
            'en_progreso' => (clone $baseCountQuery)->where('status', Gap::STATUS_EN_PROGRESO)->count(),
            'resueltos' => (clone $baseCountQuery)->where('status', Gap::STATUS_RESUELTO)->count(),
            'verificados' => (clone $baseCountQuery)->where('status', Gap::STATUS_VERIFICADO)->count(),
            'vencidos' => (clone $baseCountQuery)
                ->where('due_date', '<', now()->toDateString())
                ->whereNotIn('status', [Gap::STATUS_RESUELTO, Gap::STATUS_VERIFICADO, Gap::STATUS_CERRADO])
                ->count(),
        ];

        $availableUsers = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $viewingGap = null;
        if ($this->showDetailModal && $this->viewingGapId) {
            $viewingGap = Gap::with([
                'practiceEvaluation.appraisal.project',
                'practiceEvaluation.practice',
                'practiceCriterion',
                'evidence',
                'assignedTo',
                'generatedBy',
                'logs.user',
            ])->find($this->viewingGapId);
        }

        return view('livewire.gaps.gap-management', [
            'gaps' => $gaps,
            'counts' => $counts,
            'availableUsers' => $availableUsers,
            'viewingGap' => $viewingGap,
            'canEdit' => $authUser->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS),
        ])->layout('layouts.app');
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'title.required' => 'El título del gap es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'severity.required' => 'Debe seleccionar un nivel de severidad.',
            'status.required' => 'Debe seleccionar un estado.',
            'assignedToId.exists' => 'El usuario seleccionado no es válido.',
            'dueDate.date' => 'La fecha límite debe ser una fecha válida.',
        ];
    }
}
