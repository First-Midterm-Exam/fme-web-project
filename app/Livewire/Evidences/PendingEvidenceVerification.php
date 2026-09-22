<?php

namespace App\Livewire\Evidences;

use App\Actions\Evidences\VerifyEvidenceAction;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PendingEvidenceVerification extends Component
{
    use AuthorizesRequests, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $search = '';

    public string $projectFilter = 'all';

    public bool $showDetailModal = false;

    public bool $showVerifyModal = false;

    public ?Evidence $selectedEvidence = null;

    public ?int $verificationStatus = EvidenceStatus::VERIFICADA;

    public string $verificationReason = '';

    /**
     * @var array<string, array{except: string}>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'projectFilter' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->authorize('viewPending', Evidence::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingProjectFilter(): void
    {
        $this->resetPage();
    }

    public function openDetailModal(int $evidenceId): void
    {
        $evidence = Evidence::with(['project', 'status', 'uploadedBy', 'currentVersion', 'practices.practiceArea', 'versions.uploadedBy'])
            ->findOrFail($evidenceId);

        $this->authorize('view', $evidence);

        $this->selectedEvidence = $evidence;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedEvidence = null;
    }

    public function openVerifyModal(int $evidenceId): void
    {
        $evidence = Evidence::with(['project', 'status', 'uploadedBy', 'currentVersion'])
            ->findOrFail($evidenceId);

        $this->authorize('verify', $evidence);

        $this->selectedEvidence = $evidence;
        $this->verificationStatus = EvidenceStatus::VERIFICADA;
        $this->verificationReason = '';
        $this->resetValidation();
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal(): void
    {
        $this->showVerifyModal = false;
        $this->selectedEvidence = null;
        $this->verificationStatus = EvidenceStatus::VERIFICADA;
        $this->verificationReason = '';
        $this->resetValidation();
    }

    public function submitVerification(VerifyEvidenceAction $action): void
    {
        if (! $this->selectedEvidence) {
            return;
        }

        $this->authorize('verify', $this->selectedEvidence);

        $this->validate([
            'verificationStatus' => [
                'required',
                'integer',
                Rule::in([EvidenceStatus::VERIFICADA, EvidenceStatus::OBSERVADA, EvidenceStatus::RECHAZADA]),
            ],
            'verificationReason' => [
                Rule::requiredIf(fn (): bool => in_array((int) $this->verificationStatus, [EvidenceStatus::OBSERVADA, EvidenceStatus::RECHAZADA], true)),
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'verificationStatus.required' => 'Debe seleccionar una decisión de verificación.',
            'verificationStatus.in' => 'El estado de verificación seleccionado no es válido.',
            'verificationReason.required' => 'Debe ingresar el motivo de la justificación al observar o rechazar la evidencia.',
            'verificationReason.max' => 'El motivo de verificación no puede superar los 1000 caracteres.',
        ]);

        $statusId = (int) $this->verificationStatus;

        $action->execute(
            $this->selectedEvidence,
            auth()->user(),
            $statusId,
            $this->verificationReason
        );

        $statusLabel = match ($statusId) {
            EvidenceStatus::VERIFICADA => 'Verificado',
            EvidenceStatus::OBSERVADA => 'Observado',
            EvidenceStatus::RECHAZADA => 'Rechazado',
            default => '',
        };

        session()->flash('message', 'La evidencia '.$this->selectedEvidence->code.' fue verificada como "'.$statusLabel.'".');

        $this->closeVerifyModal();
    }

    public function render(): View
    {
        $this->authorize('viewPending', Evidence::class);

        /** @var User $authUser */
        $authUser = auth()->user();

        if ($authUser->esAdministrador()) {
            $eligibleProjects = Project::orderBy('name')->get();
        } elseif ($authUser->tieneRol(Rol::GESTOR_PROCESOS)) {
            $eligibleProjects = $authUser->projects()->orderBy('name')->get();
        } else {
            $eligibleProjects = collect();
        }

        $query = Evidence::with(['project', 'status', 'uploadedBy', 'currentVersion', 'practices'])
            ->where('status_id', EvidenceStatus::REGISTRADA);

        if (! $authUser->esAdministrador()) {
            $query->whereIn('project_id', $authUser->projects()->pluck('projects.id'));
        }

        if ($this->projectFilter !== 'all' && is_numeric($this->projectFilter)) {
            $query->where('project_id', (int) $this->projectFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        $pendingEvidences = $query->orderBy('id', 'desc')->paginate(10);

        return view('livewire.evidences.pending-evidence-verification', [
            'pendingEvidences' => $pendingEvidences,
            'eligibleProjects' => $eligibleProjects,
            'types' => Evidence::TYPES,
        ])->layout('layouts.app', ['header' => 'Verificación de Evidencias']);
    }
}
