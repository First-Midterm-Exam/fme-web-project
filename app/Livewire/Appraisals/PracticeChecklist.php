<?php

namespace App\Livewire\Appraisals;

use App\Actions\Gaps\GenerateGapAction;
use App\Models\Appraisal;
use App\Models\CriterionCheck;
use App\Models\Practice;
use App\Models\PracticeCriterion;
use App\Models\PracticeEvaluation;
use App\Models\User;
use App\Services\PracticeStatusCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PracticeChecklist extends Component
{
    use AuthorizesRequests;

    public Appraisal $appraisal;

    public Practice $practice;

    public PracticeEvaluation $evaluation;

    /**
     * @var array<int, string>
     */
    public array $marks = [];

    /**
     * @var array<int, string>
     */
    public array $notes = [];

    public ?int $editingNoteFor = null;

    public function mount(Appraisal $appraisal, Practice $practice): void
    {
        $this->appraisal = $appraisal->load('project');
        $this->practice = $practice->load('practiceArea');

        $this->authorize('view', $this->appraisal);
        $this->ensurePracticeIsInScope();

        $this->evaluation = PracticeEvaluation::firstOrCreate(
            [
                'appraisal_id' => $this->appraisal->id,
                'practice_id' => $this->practice->id,
            ],
            [
                'status' => PracticeEvaluation::STATUS_NO_EVALUADA,
            ]
        );

        $this->loadMarks();
    }

    public function markCriterion(int $criterionId, string $status): void
    {
        $this->authorize('evaluate', $this->appraisal);

        if (! in_array($status, CriterionCheck::statuses(), true)) {
            return;
        }

        if (! $this->criterionBelongsToPractice($criterionId)) {
            return;
        }

        CriterionCheck::updateOrCreate(
            [
                'practice_evaluation_id' => $this->evaluation->id,
                'practice_criterion_id' => $criterionId,
            ],
            [
                'status' => $status,
                'evaluated_by' => auth()->id(),
                'evaluated_at' => now(),
            ]
        );

        $message = 'El criterio fue marcado como "'.$status.'".';

        if ($status === CriterionCheck::STATUS_NO_CUMPLE) {
            $gap = app(GenerateGapAction::class)->fromUnmetCriterion(
                $this->evaluation,
                $this->activeCriteria()->firstWhere('id', $criterionId),
                $this->currentUser()
            );

            $message .= ' Gap '.$gap->code.' abierto.';
        }

        PracticeStatusCalculator::calculateForEvaluation($this->evaluation);
        $this->loadMarks();

        session()->flash('message', $message);
    }

    public function markAll(string $status): void
    {
        $this->authorize('evaluate', $this->appraisal);

        if (! in_array($status, CriterionCheck::statuses(), true)) {
            return;
        }

        DB::transaction(function () use ($status): void {
            foreach ($this->activeCriteria() as $criterion) {
                CriterionCheck::updateOrCreate(
                    [
                        'practice_evaluation_id' => $this->evaluation->id,
                        'practice_criterion_id' => $criterion->id,
                    ],
                    [
                        'status' => $status,
                        'evaluated_by' => auth()->id(),
                        'evaluated_at' => now(),
                    ]
                );
            }
        });

        $message = 'Se marcaron todos los criterios como "'.$status.'".';

        if ($status === CriterionCheck::STATUS_NO_CUMPLE) {
            $generator = app(GenerateGapAction::class);
            $gaps = $this->activeCriteria()->map(
                fn (PracticeCriterion $criterion) => $generator->fromUnmetCriterion($this->evaluation, $criterion, $this->currentUser())
            );

            $message .= ' Gaps abiertos: '.$gaps->pluck('code')->implode(', ').'.';
        }

        PracticeStatusCalculator::calculateForEvaluation($this->evaluation);
        $this->loadMarks();

        session()->flash('message', $message);
    }

    public function editNote(int $criterionId): void
    {
        $this->authorize('evaluate', $this->appraisal);

        $this->editingNoteFor = $criterionId;
    }

    public function cancelNote(): void
    {
        $this->editingNoteFor = null;
        $this->loadMarks();
    }

    public function saveNote(int $criterionId): void
    {
        $this->authorize('evaluate', $this->appraisal);

        if (! $this->criterionBelongsToPractice($criterionId)) {
            return;
        }

        $this->validate([
            'notes.'.$criterionId => ['nullable', 'string', 'max:1000'],
        ], [
            'notes.*.max' => 'La observación no puede superar los 1000 caracteres.',
        ]);

        $note = trim($this->notes[$criterionId] ?? '');

        CriterionCheck::updateOrCreate(
            [
                'practice_evaluation_id' => $this->evaluation->id,
                'practice_criterion_id' => $criterionId,
            ],
            [
                'notes' => $note === '' ? null : $note,
            ]
        );

        $this->editingNoteFor = null;
        $this->loadMarks();

        session()->flash('message', 'La observación del criterio fue guardada.');
    }

    public function render(): View
    {
        $this->authorize('view', $this->appraisal);

        return view('livewire.appraisals.practice-checklist', [
            'criteria' => $this->activeCriteria(),
            'statuses' => CriterionCheck::statuses(),
            'percentage' => $this->evaluation->compliancePercentage(),
            'metCount' => $this->evaluation->metCriteriaCount(),
            'applicableCount' => $this->evaluation->applicableCriteriaCount(),
            'gaps' => $this->evaluation->gaps()
                ->abiertos()
                ->whereNotNull('practice_criterion_id')
                ->get()
                ->keyBy('practice_criterion_id'),
        ])->layout('layouts.app', ['header' => 'Checklist de Criterios']);
    }

    private function loadMarks(): void
    {
        $this->evaluation->load('criterionChecks');
        $this->practice->load(['criteria', 'practiceArea']);

        $stored = $this->evaluation->criterionChecks->keyBy('practice_criterion_id');

        $this->marks = [];
        $this->notes = [];

        foreach ($this->activeCriteria() as $criterion) {
            $mark = $stored->get($criterion->id);

            $this->marks[$criterion->id] = $mark instanceof CriterionCheck
                ? $mark->status
                : CriterionCheck::STATUS_PENDIENTE;

            $this->notes[$criterion->id] = $mark instanceof CriterionCheck
                ? (string) $mark->notes
                : '';
        }
    }

    /**
     * @return Collection<int, PracticeCriterion>
     */
    private function activeCriteria(): Collection
    {
        return $this->practice->criteria->where('estado', true)->values();
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    private function criterionBelongsToPractice(int $criterionId): bool
    {
        return $this->activeCriteria()->contains('id', $criterionId);
    }

    private function ensurePracticeIsInScope(): void
    {
        $inScope = $this->appraisal->practices()
            ->where('practices.id', $this->practice->id)
            ->exists();

        abort_unless($inScope, 404);
    }
}
