<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectManagement extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    // -------------------------------------------------------------------------
    // Form state — create / edit
    // -------------------------------------------------------------------------

    public ?int $projectId = null;

    public string $name = '';

    public string $code = '';

    public string $startDate = '';

    public bool $showFormModal = false;

    public bool $isEditing = false;

    // -------------------------------------------------------------------------
    // Members modal state
    // -------------------------------------------------------------------------

    public ?int $membersProjectId = null;

    public ?int $selectedUserId = null;

    public bool $showMembersModal = false;

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
        $this->authorize('viewAny', Project::class);
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
        $this->authorize('create', Project::class);

        $this->resetFormFields();
        $this->isEditing = false;
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        $this->projectId = $project->id;
        $this->name = $project->name;
        $this->code = $project->code;
        $this->startDate = Carbon::parse($project->start_date)->format('Y-m-d');
        $this->isEditing = true;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetFormFields();
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->isEditing && $this->projectId) {
            $project = Project::findOrFail($this->projectId);
            $this->authorize('update', $project);

            $validated = $this->validate([
                'name' => 'required|string|max:255',
                'code' => ['required', 'string', 'max:50', Rule::unique('projects', 'code')->ignore($project->id)],
                'startDate' => 'required|date',
            ], $this->messages());

            $project->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'start_date' => $validated['startDate'],
            ]);

            session()->flash('message', 'Proyecto actualizado exitosamente.');
        } else {
            $this->authorize('create', Project::class);

            $validated = $this->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:projects,code',
                'startDate' => 'required|date',
            ], $this->messages());

            Project::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'start_date' => $validated['startDate'],
                'status' => 'activo',
            ]);

            session()->flash('message', 'Proyecto creado exitosamente.');
        }

        $this->closeFormModal();
    }

    // -------------------------------------------------------------------------
    // Close project
    // -------------------------------------------------------------------------

    public function closeProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('close', $project);

        $project->update(['status' => 'cerrado']);

        session()->flash('message', "El proyecto \"{$project->name}\" ha sido cerrado.");
    }

    // -------------------------------------------------------------------------
    // Members management
    // -------------------------------------------------------------------------

    public function openMembersModal(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('manageMembers', $project);

        $this->membersProjectId = $id;
        $this->selectedUserId = null;
        $this->showMembersModal = true;
    }

    public function closeMembersModal(): void
    {
        $this->showMembersModal = false;
        $this->membersProjectId = null;
        $this->selectedUserId = null;
    }

    public function addMember(): void
    {
        $project = Project::findOrFail($this->membersProjectId);
        $this->authorize('manageMembers', $project);

        $this->validate([
            'selectedUserId' => 'required|integer|exists:users,id',
        ], [
            'selectedUserId.required' => 'Debe seleccionar un usuario.',
            'selectedUserId.exists' => 'El usuario seleccionado no existe.',
        ]);

        /** @var int $userId */
        $userId = $this->selectedUserId;

        if (! $project->users()->where('users.id', $userId)->exists()) {
            $project->users()->attach($userId);
        }

        $this->selectedUserId = null;
        session()->flash('membersMessage', 'Integrante agregado exitosamente.');
    }

    public function removeMember(int $userId): void
    {
        $project = Project::findOrFail($this->membersProjectId);
        $this->authorize('manageMembers', $project);

        $project->users()->detach($userId);

        session()->flash('membersMessage', 'Integrante eliminado del proyecto.');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $this->authorize('viewAny', Project::class);

        /** @var User $authUser */
        $authUser = auth()->user();

        $statusCounts = [
            'total' => Project::visibleFor($authUser)->count(),
            'activos' => Project::visibleFor($authUser)->where('status', 'activo')->count(),
            'cerrados' => Project::visibleFor($authUser)->where('status', 'cerrado')->count(),
            'miembros' => DB::table('project_user')->distinct('user_id')->count('user_id'),
        ];

        $projects = Project::with(['users.roles'])
            ->visibleFor($authUser)
            ->when($this->statusFilter === 'activo', fn ($q) => $q->where('status', 'activo'))
            ->when($this->statusFilter === 'cerrado', fn ($q) => $q->where('status', 'cerrado'))
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        $membersProject = $this->membersProjectId
            ? Project::with(['users.roles'])->findOrFail($this->membersProjectId)
            : null;

        $availableUsers = User::with('roles')->orderBy('name')->get();

        return view('livewire.projects.project-management', [
            'projects' => $projects,
            'membersProject' => $membersProject,
            'availableUsers' => $availableUsers,
            'statusCounts' => $statusCounts,
        ])->layout('layouts.app');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resetFormFields(): void
    {
        $this->projectId = null;
        $this->name = '';
        $this->code = '';
        $this->startDate = '';
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre del proyecto es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'code.required' => 'El código del proyecto es obligatorio.',
            'code.max' => 'El código no puede superar los 50 caracteres.',
            'code.unique' => 'Ya existe un proyecto con ese código.',
            'startDate.required' => 'La fecha de inicio es obligatoria.',
            'startDate.date' => 'La fecha de inicio debe ser una fecha válida.',
        ];
    }
}
