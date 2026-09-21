<?php

namespace App\Livewire\Appraisals;

use App\Models\Appraisal;
use App\Models\Project;
use App\Models\User;
use App\Services\AppraisalStateService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class AppraisalManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // -------------------------------------------------------------------------
    // Form state
    // -------------------------------------------------------------------------

    public ?int $appraisalId = null;

    public ?int $projectId = null;

    public string $name = '';

    public string $domain = 'Development';

    public int $targetLevel = 3;

    public string $targetDate = '';

    public bool $showFormModal = false;

    public bool $isEditing = false;

    public string $currentStatus = 'borrador';

    // -------------------------------------------------------------------------
    // Search & Filters
    // -------------------------------------------------------------------------

    public string $search = '';

    public string $statusFilter = 'all';

    /**
     * @var array<string, array{except: string}>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function mount(): void
    {
        $this->authorize('viewAny', Appraisal::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    // -------------------------------------------------------------------------
    // Create / Edit
    // -------------------------------------------------------------------------

    public function openCreateModal(): void
    {
        $this->authorize('create', Appraisal::class);

        $this->resetFormFields();
        $this->isEditing = false;
        $this->currentStatus = Appraisal::STATUS_BORRADOR;
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $appraisal = Appraisal::with('project')->findOrFail($id);
        $this->authorize('update', $appraisal);

        $this->appraisalId = $appraisal->id;
        $this->projectId = $appraisal->project_id;
        $this->name = $appraisal->name;
        $this->domain = $appraisal->domain;
        $this->targetLevel = $appraisal->target_level;
        $this->targetDate = Carbon::parse($appraisal->target_date)->format('Y-m-d');
        $this->currentStatus = $appraisal->status;
        $this->isEditing = true;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetFormFields();
        $this->resetValidation();
    }

    public function save(AppraisalStateService $stateService): void
    {
        $this->validate([
            'projectId' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:100'],
            'targetLevel' => ['required', 'integer', 'min:1', 'max:5'],
            'targetDate' => ['required', 'date'],
        ], $this->messages());

        if ($this->isEditing && $this->appraisalId) {
            $appraisal = Appraisal::with('project')->findOrFail($this->appraisalId);
            $this->authorize('update', $appraisal);

            // Server-side validation of project immutability
            $stateService->validateProjectChange($appraisal, (int) $this->projectId);

            $appraisal->update([
                'project_id' => $this->projectId,
                'name' => $this->name,
                'domain' => $this->domain,
                'target_level' => $this->targetLevel,
                'target_date' => $this->targetDate,
            ]);

            session()->flash('message', 'Appraisal actualizado con éxito.');
        } else {
            $this->authorize('create', Appraisal::class);

            // Validate project is active
            $stateService->validateCreation((int) $this->projectId);

            Appraisal::create([
                'project_id' => $this->projectId,
                'name' => $this->name,
                'domain' => $this->domain,
                'target_level' => $this->targetLevel,
                'target_date' => $this->targetDate,
                'status' => Appraisal::STATUS_BORRADOR,
            ]);

            session()->flash('message', 'Appraisal creado exitosamente en estado borrador.');
        }

        $this->closeFormModal();
    }

    // -------------------------------------------------------------------------
    // State Transitions
    // -------------------------------------------------------------------------

    public function activateAppraisal(int $id, AppraisalStateService $stateService): void
    {
        $appraisal = Appraisal::with('project')->findOrFail($id);
        $this->authorize('updateStatus', $appraisal);

        try {
            $stateService->activate($appraisal);
            session()->flash('message', "El appraisal '{$appraisal->name}' ha sido activado.");
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeAppraisal(int $id, AppraisalStateService $stateService): void
    {
        $appraisal = Appraisal::with('project')->findOrFail($id);
        $this->authorize('updateStatus', $appraisal);

        try {
            $stateService->close($appraisal);
            session()->flash('message', "El appraisal '{$appraisal->name}' ha sido cerrado y ahora es de solo lectura.");
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $this->authorize('viewAny', Appraisal::class);

        /** @var User $authUser */
        $authUser = auth()->user();

        $statusCounts = [
            'total' => Appraisal::visibleFor($authUser)->count(),
            'borrador' => Appraisal::visibleFor($authUser)->where('status', Appraisal::STATUS_BORRADOR)->count(),
            'activo' => Appraisal::visibleFor($authUser)->where('status', Appraisal::STATUS_ACTIVO)->count(),
            'cerrado' => Appraisal::visibleFor($authUser)->where('status', Appraisal::STATUS_CERRADO)->count(),
        ];

        $appraisals = Appraisal::with('project')
            ->visibleFor($authUser)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('domain', 'like', '%'.$this->search.'%')
                        ->orWhereHas('project', fn ($pq) => $pq->where('name', 'like', '%'.$this->search.'%')->orWhere('code', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        // Active projects visible to the user for appraisal creation
        $availableProjects = Project::visibleFor($authUser)
            ->where('status', 'activo')
            ->orderBy('name')
            ->get();

        return view('livewire.appraisals.appraisal-management', [
            'appraisals' => $appraisals,
            'availableProjects' => $availableProjects,
            'statusCounts' => $statusCounts,
        ])->layout('layouts.app');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resetFormFields(): void
    {
        $this->appraisalId = null;
        $this->projectId = null;
        $this->name = '';
        $this->domain = 'Development';
        $this->targetLevel = 3;
        $this->targetDate = '';
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'projectId.required' => 'Debe seleccionar un proyecto.',
            'projectId.exists' => 'El proyecto seleccionado no es válido.',
            'name.required' => 'El nombre del appraisal es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'domain.required' => 'El dominio CMMI es obligatorio.',
            'targetLevel.required' => 'El nivel objetivo es obligatorio.',
            'targetLevel.min' => 'El nivel objetivo mínimo es 1.',
            'targetLevel.max' => 'El nivel objetivo máximo es 5.',
            'targetDate.required' => 'La fecha meta es obligatoria.',
            'targetDate.date' => 'La fecha meta debe ser una fecha válida.',
        ];
    }
}
