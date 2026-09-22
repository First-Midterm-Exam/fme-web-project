<?php

use App\Actions\Gaps\ValidateGapClosureAction;
use App\Livewire\Gaps\GapClosureValidation;
use App\Models\Appraisal;
use App\Models\CorrectiveAction;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        EvidenceStatusSeeder::class,
        CmmiCatalogSeeder::class,
    ]);
});

function createResolvedGapForClosure(array $projectUsers = []): array
{
    $processManager = User::factory()->gestorProcesos()->create();
    $projectManager = User::factory()->jefeProyecto()->create();
    $collaborator = User::factory()->colaborador()->create();

    $project = Project::create([
        'name' => 'Proyecto Test HU-20',
        'code' => 'PROJ-'.rand(1000, 9999),
        'start_date' => now()->toDateString(),
        'status' => 'activo',
    ]);

    $allUsers = array_merge([$processManager->id, $projectManager->id, $collaborator->id], collect($projectUsers)->pluck('id')->all());
    $project->users()->attach(array_unique($allUsers));

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal HU-20',
        'domain' => 'Desarrollo',
        'target_level' => 2,
        'target_date' => now()->addMonths(6)->toDateString(),
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $evaluation = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => 1,
        'status' => 'No cumple',
    ]);

    $evidence = Evidence::create([
        'code' => Evidence::generateNextCode(),
        'project_id' => $project->id,
        'name' => 'Evidencia Solucion Test',
        'type' => Evidence::TYPE_INFORME,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $collaborator->id,
    ]);

    $gap = Gap::create([
        'code' => Gap::generateNextCode(),
        'practice_evaluation_id' => $evaluation->id,
        'title' => 'Gap Resuelto Test HU-20',
        'description' => 'Descripción del gap resuelto',
        'status' => Gap::STATUS_RESUELTO,
        'severity' => Gap::SEVERITY_MEDIA,
        'assigned_to_id' => $collaborator->id,
        'due_date' => now()->addDays(10)->toDateString(),
        'generated_by' => $projectManager->id,
    ]);

    $action = CorrectiveAction::create([
        'gap_id' => $gap->id,
        'description' => 'Acción correctiva completada',
        'responsible_id' => $collaborator->id,
        'due_date' => now()->addDays(5)->toDateString(),
        'progress_percent' => 100,
        'status' => CorrectiveAction::STATUS_EN_PROGRESO,
        'solution_evidence_id' => $evidence->id,
    ]);

    return [$gap, $action, $processManager, $projectManager, $project];
}

test('a) aprobar un Gap Resuelto con acceso al proyecto -> Gap queda Cerrado con closed_by/closed_at y CorrectiveAction queda cerrada', function (): void {
    [$gap, $action, $processManager] = createResolvedGapForClosure();

    Livewire::actingAs($processManager)
        ->test(GapClosureValidation::class)
        ->call('openValidationModal', $gap->id)
        ->set('decision', 'approve')
        ->call('submitValidation')
        ->assertHasNoErrors();

    $gap->refresh();
    $action->refresh();

    expect($gap->status)->toBe(Gap::STATUS_CERRADO)
        ->and($gap->closed_by)->toBe($processManager->id)
        ->and($gap->closed_at)->not()->toBeNull()
        ->and($action->status)->toBe(CorrectiveAction::STATUS_CERRADA);
});

test('b) rechazar sin motivo es rechazado con mensaje de validacion', function (): void {
    [$gap, $action, $processManager] = createResolvedGapForClosure();

    Livewire::actingAs($processManager)
        ->test(GapClosureValidation::class)
        ->call('openValidationModal', $gap->id)
        ->set('decision', 'reject')
        ->set('rejectionReason', '  ')
        ->call('submitValidation')
        ->assertHasErrors(['rejectionReason']);

    $gap->refresh();
    $action->refresh();

    expect($gap->status)->toBe(Gap::STATUS_RESUELTO)
        ->and($action->status)->toBe(CorrectiveAction::STATUS_EN_PROGRESO);
});

test('c) rechazar con motivo -> Gap vuelve a En progreso, motivo queda en GapLog', function (): void {
    [$gap, $action, $processManager] = createResolvedGapForClosure();

    Livewire::actingAs($processManager)
        ->test(GapClosureValidation::class)
        ->call('openValidationModal', $gap->id)
        ->set('decision', 'reject')
        ->set('rejectionReason', 'La evidencia adjunta no contiene la firma de revisión.')
        ->call('submitValidation')
        ->assertHasNoErrors();

    $gap->refresh();
    $action->refresh();

    expect($gap->status)->toBe(Gap::STATUS_EN_PROGRESO)
        ->and($action->status)->toBe(CorrectiveAction::STATUS_EN_PROGRESO)
        ->and($action->progress_percent)->toBe(90);

    $lastLog = $gap->logs->first();
    expect($lastLog->new_value)->toBe(Gap::STATUS_EN_PROGRESO)
        ->and($lastLog->description)->toContain('La evidencia adjunta no contiene la firma de revisión.');
});

test('d) un Gap que no esta en Resuelto no puede validarse desde esta pantalla', function (): void {
    [$gap, $action, $processManager] = createResolvedGapForClosure();
    $gap->update(['status' => Gap::STATUS_EN_PROGRESO]);

    Livewire::actingAs($processManager)
        ->test(GapClosureValidation::class)
        ->assertViewHas('resolvedGaps', function ($resolvedGaps) use ($gap): bool {
            return ! $resolvedGaps->contains('id', $gap->id);
        })
        ->call('openValidationModal', $gap->id)
        ->assertForbidden();
});

test('e) un usuario sin acceso al proyecto no puede validar el Gap', function (): void {
    $processManagerUnassigned = User::factory()->gestorProcesos()->create();
    [$gap, $action, $processManager] = createResolvedGapForClosure();

    Livewire::actingAs($processManagerUnassigned)
        ->test(GapClosureValidation::class)
        ->assertViewHas('resolvedGaps', function ($resolvedGaps) use ($gap): bool {
            return ! $resolvedGaps->contains('id', $gap->id);
        })
        ->call('openValidationModal', $gap->id)
        ->assertForbidden();

    expect(fn () => app(ValidateGapClosureAction::class)->execute($gap, $processManagerUnassigned, 'approve'))
        ->toThrow(AuthorizationException::class);
});

test('f) un Gap ya Cerrado no puede volver a aparecer en el listado ni validarse de nuevo', function (): void {
    [$gap, $action, $processManager] = createResolvedGapForClosure();

    app(ValidateGapClosureAction::class)->execute($gap, $processManager, 'approve');
    $gap->refresh();
    expect($gap->status)->toBe(Gap::STATUS_CERRADO);

    Livewire::actingAs($processManager)
        ->test(GapClosureValidation::class)
        ->assertViewHas('resolvedGaps', function ($resolvedGaps) use ($gap): bool {
            return ! $resolvedGaps->contains('id', $gap->id);
        })
        ->call('openValidationModal', $gap->id)
        ->assertForbidden();
});
