<?php

namespace App\Livewire\Gaps;

use App\Actions\Gaps\UpdateCorrectiveActionProgressAction;
use App\Models\CorrectiveAction;
use App\Models\Evidence;
use App\Models\Gap;
use App\Models\User;
use App\Services\CorrectiveActionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * @property-read Collection<int, User> $availableUsers
 */
class GapDetail extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Gap $gap;

    public bool $showingCreateModal = false;

    public bool $showingUpdateProgressModal = false;

    public string $description = '';

    public ?int $responsible_id = null;

    public string $due_date = '';

    public int $progress_percent = 0;

    /**
     * @var mixed
     */
    public $solution_file;

    public string $progress_comment = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(Gap $gap): void
    {
        $this->gap = $gap;
        $this->authorize('view', $this->gap);
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', [CorrectiveAction::class, $this->gap]);

        if ($this->gap->hasActiveCorrectiveAction()) {
            $this->errorMessage = 'Este gap ya cuenta con una acción correctiva activa.';

            return;
        }

        $this->reset(['description', 'responsible_id', 'due_date', 'errorMessage']);
        $this->due_date = now()->addDays(7)->format('Y-m-d');
        $this->showingCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showingCreateModal = false;
        $this->resetValidation();
    }

    public function openUpdateProgressModal(): void
    {
        $activeAction = $this->gap->activeCorrectiveAction();

        if (! $activeAction) {
            $this->errorMessage = 'No hay una acción correctiva activa para actualizar.';

            return;
        }

        $this->authorize('update', $activeAction);

        $this->progress_percent = $activeAction->progress_percent;
        $this->solution_file = null;
        $this->progress_comment = '';
        $this->resetValidation();
        $this->showingUpdateProgressModal = true;
    }

    public function closeUpdateProgressModal(): void
    {
        $this->showingUpdateProgressModal = false;
        $this->solution_file = null;
        $this->progress_comment = '';
        $this->resetValidation();
    }

    public function createCorrectiveAction(CorrectiveActionService $service): void
    {
        $this->authorize('create', [CorrectiveAction::class, $this->gap]);

        $validated = $this->validate([
            'description' => ['required', 'string', 'min:5'],
            'responsible_id' => ['required', 'integer', 'exists:users,id'],
            'due_date' => ['required', 'date'],
        ], [
            'description.required' => 'La descripción de la acción correctiva es obligatoria.',
            'description.min' => 'La descripción debe tener al menos 5 caracteres.',
            'responsible_id.required' => 'El responsable asignado es obligatorio.',
            'responsible_id.exists' => 'El usuario seleccionado no es válido.',
            'due_date.required' => 'La fecha límite es obligatoria.',
            'due_date.date' => 'La fecha límite debe ser una fecha válida.',
        ]);

        try {
            /** @var User $currentUser */
            $currentUser = auth()->user();

            $service->createForGap($this->gap, [
                'description' => $validated['description'],
                'responsible_id' => (int) $validated['responsible_id'],
                'due_date' => $validated['due_date'],
            ], $currentUser);

            $this->gap->refresh();
            $this->showingCreateModal = false;
            $this->successMessage = 'Acción correctiva creada exitosamente. El gap ha pasado a "En progreso".';
            $this->errorMessage = null;
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first('gap') ?: $e->getMessage();
            $this->addError('gap', $message);
        }
    }

    public function updateProgress(UpdateCorrectiveActionProgressAction $actionService): void
    {
        $activeAction = $this->gap->activeCorrectiveAction();

        if (! $activeAction) {
            $this->errorMessage = 'No hay una acción correctiva activa para actualizar.';

            return;
        }

        $this->authorize('update', $activeAction);

        $this->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'solution_file' => ['nullable', 'file', 'max:'.Evidence::MAX_FILE_SIZE_KB, 'mimes:'.implode(',', Evidence::ALLOWED_EXTENSIONS)],
            'progress_comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'progress_percent.required' => 'El porcentaje de avance es obligatorio.',
            'progress_percent.integer' => 'El avance debe ser un número entero.',
            'progress_percent.min' => 'El avance no puede ser menor a 0%.',
            'progress_percent.max' => 'El avance no puede ser mayor a 100%.',
            'solution_file.max' => 'El archivo supera el tamaño máximo permitido de 10 MB.',
            'solution_file.mimes' => 'El tipo de archivo no está permitido.',
            'progress_comment.max' => 'El comentario no puede superar los 1000 caracteres.',
        ]);

        try {
            /** @var User $currentUser */
            $currentUser = auth()->user();

            $actionService->execute(
                $activeAction,
                $currentUser,
                (int) $this->progress_percent,
                $this->solution_file,
                $this->progress_comment
            );

            $this->gap->refresh();
            $this->showingUpdateProgressModal = false;
            $this->solution_file = null;
            $this->progress_comment = '';
            $this->successMessage = 'Avance de la acción correctiva actualizado exitosamente.';
            $this->errorMessage = null;
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function getAvailableUsersProperty()
    {
        $project = $this->gap->practiceEvaluation?->appraisal?->project;

        if ($project && $project->users()->count() > 0) {
            return $project->users()->where('is_active', true)->orderBy('name')->get();
        }

        return User::where('is_active', true)->orderBy('name')->get();
    }

    public function render(): View
    {
        $this->gap->load([
            'practiceEvaluation.appraisal.project',
            'practiceEvaluation.practice.practiceArea',
            'practiceCriterion',
            'assignedTo',
            'generatedBy',
            'correctiveActions.responsible',
            'correctiveActions.solutionEvidence',
            'correctiveActions.logs.user',
            'logs.user',
        ]);

        return view('livewire.gaps.gap-detail', [
            'availableUsers' => $this->availableUsers,
            'activeAction' => $this->gap->activeCorrectiveAction(),
        ])->layout('layouts.app', ['titulo' => "Detalle de {$this->gap->code}"]);
    }
}
