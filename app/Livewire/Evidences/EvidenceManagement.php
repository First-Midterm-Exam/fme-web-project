<?php

namespace App\Livewire\Evidences;

use App\Actions\Evidences\RegisterEvidenceAction;
use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\Practice;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EvidenceManagement extends Component
{
    use AuthorizesRequests, WithFileUploads, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public ?int $projectId = null;

    public string $name = '';

    public string $type = '';

    public string $description = '';

    /**
     * @var mixed
     */
    public $file;

    public array $selectedPractices = [];

    public bool $showCreateModal = false;

    public string $search = '';

    public string $projectFilter = 'all';

    /**
     * @var array<string, array{except: string}>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'projectFilter' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Evidence::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProjectFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Evidence::class);

        $this->resetFormFields();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetFormFields();
        $this->resetValidation();
    }

    public function save(RegisterEvidenceAction $action): void
    {
        $this->authorize('create', Evidence::class);

        $validated = $this->validate([
            'projectId' => [
                'required',
                'integer',
                'exists:projects,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $project = Project::find($value);
                    if (! $project) {
                        return;
                    }

                    /** @var User $authUser */
                    $authUser = auth()->user();

                    if (! $authUser->can('create', [Evidence::class, $project])) {
                        $fail('No está autorizado para registrar evidencias en este proyecto o el proyecto no tiene un appraisal en curso (RN-10).');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(Evidence::TYPES))],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:'.Evidence::MAX_FILE_SIZE_KB, 'mimes:'.implode(',', Evidence::ALLOWED_EXTENSIONS)],
            'selectedPractices' => ['nullable', 'array'],
            'selectedPractices.*' => ['integer', 'exists:practices,id'],
        ], $this->messages());

        $project = Project::findOrFail($validated['projectId']);

        /** @var User $authUser */
        $authUser = auth()->user();

        $action->execute(
            $project,
            $authUser,
            $this->file,
            [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
            ],
            array_map('intval', $this->selectedPractices)
        );

        session()->flash('message', 'Evidencia registrada exitosamente.');

        $this->closeCreateModal();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Evidence::class);

        /** @var User $authUser */
        $authUser = auth()->user();

        $eligibleProjectsQuery = Project::query()
            ->whereHas('appraisals', function ($query): void {
                $query->where('status', Appraisal::STATUS_ACTIVO);
            });

        if (! $authUser->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            $eligibleProjectsQuery->whereHas('users', function ($q) use ($authUser): void {
                $q->where('users.id', $authUser->id);
            });
        }

        $eligibleProjects = $eligibleProjectsQuery->orderBy('name')->get();

        $evidencesQuery = Evidence::with(['project', 'status', 'uploadedBy', 'currentVersion', 'practices']);

        if (! $authUser->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            $evidencesQuery->whereIn('project_id', $authUser->projects()->pluck('projects.id'));
        }

        if ($this->projectFilter !== 'all' && is_numeric($this->projectFilter)) {
            $evidencesQuery->where('project_id', (int) $this->projectFilter);
        }

        if ($this->search !== '') {
            $evidencesQuery->where(function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        $evidences = $evidencesQuery->orderBy('id', 'desc')->paginate(10);
        $availablePractices = Practice::orderBy('code')->get();

        return view('livewire.evidences.evidence-management', [
            'evidences' => $evidences,
            'eligibleProjects' => $eligibleProjects,
            'types' => Evidence::TYPES,
            'availablePractices' => $availablePractices,
        ])->layout('layouts.app');

        return view('livewire.evidences.evidence-management', [
            'evidences' => $evidences,
            'eligibleProjects' => $eligibleProjects,
            'types' => Evidence::TYPES,
        ])->layout('layouts.app');
    }

    private function resetFormFields(): void
    {
        $this->projectId = null;
        $this->name = '';
        $this->type = '';
        $this->description = '';
        $this->file = null;
        $this->selectedPractices = [];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'projectId.required' => 'Debe seleccionar un proyecto.',
            'projectId.exists' => 'El proyecto seleccionado no existe.',
            'name.required' => 'El nombre de la evidencia es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'type.required' => 'Debe seleccionar el tipo de evidencia.',
            'type.in' => 'El tipo de evidencia seleccionado no es válido.',
            'file.required' => 'Debe adjuntar un archivo de evidencia.',
            'file.file' => 'El archivo adjunto no es válido.',
            'file.max' => 'El archivo supera el tamaño máximo permitido de 10 MB (RNF-13).',
            'file.mimes' => 'El tipo de archivo no está permitido. Tipos permitidos: PDF, Word, Excel, PowerPoint, imágenes (PNG, JPG, JPEG) y texto (TXT).',
        ];
    }
}
