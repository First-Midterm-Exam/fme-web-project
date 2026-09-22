<?php

use App\Livewire\Gaps\GapDetail;
use App\Models\Appraisal;
use App\Models\CorrectiveAction;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use App\Services\CorrectiveActionService;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrador()->create();
    $this->gestor = User::factory()->gestorProcesos()->create();
    $this->jefe = User::factory()->jefeProyecto()->create();
    $this->colaborador = User::factory()->colaborador()->create();
    $this->colaboradorSinProyecto = User::factory()->colaborador()->create();

    $this->proyecto = Project::create([
        'name' => 'Proyecto Portal Web',
        'code' => 'PORT-01',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $this->proyecto->users()->attach([$this->jefe->id, $this->colaborador->id]);

    $this->appraisal = Appraisal::create([
        'project_id' => $this->proyecto->id,
        'name' => 'Appraisal Portal 2026',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'activo',
    ]);

    $area = PracticeArea::firstOrCreate(['code' => 'PLAN'], ['name' => 'Planning']);

    $this->practice = Practice::first() ?? Practice::create([
        'practice_area_id' => $area->id,
        'code' => 'PLAN 1.1',
        'name' => 'Plan de Trabajo',
        'level' => 1,
    ]);

    $this->evaluation = PracticeEvaluation::create([
        'appraisal_id' => $this->appraisal->id,
        'practice_id' => $this->practice->id,
        'status' => PracticeEvaluation::STATUS_NO_CUMPLE,
    ]);

    $this->gap = Gap::create([
        'code' => 'GAP-TEST-001',
        'practice_evaluation_id' => $this->evaluation->id,
        'title' => 'Falta evidencia de cronograma del proyecto',
        'status' => Gap::STATUS_ABIERTO,
        'severity' => Gap::SEVERITY_ALTA,
        'due_date' => now()->addDays(5)->toDateString(),
    ]);
});

test('un gestor de procesos o administrador puede crear una accion correctiva con responsable y fecha limite', function ($usuario) {
    $this->actingAs($usuario);

    $service = app(CorrectiveActionService::class);
    $accion = $service->createForGap($this->gap, [
        'description' => 'Elaborar y formalizar cronograma con el cliente',
        'responsible_id' => $this->colaborador->id,
        'due_date' => now()->addDays(10)->toDateString(),
    ], $usuario);

    expect($accion)->toBeInstanceOf(CorrectiveAction::class)
        ->and($accion->description)->toBe('Elaborar y formalizar cronograma con el cliente')
        ->and($accion->responsible_id)->toBe($this->colaborador->id)
        ->and($accion->progress_percent)->toBe(0)
        ->and($accion->status)->toBe(CorrectiveAction::STATUS_ABIERTA);

    $this->assertDatabaseHas('corrective_actions', [
        'id' => $accion->id,
        'gap_id' => $this->gap->id,
        'responsible_id' => $this->colaborador->id,
        'progress_percent' => 0,
    ]);
})->with([
    fn () => User::factory()->gestorProcesos()->create(),
    fn () => User::factory()->administrador()->create(),
]);

test('al crear la accion correctiva el gap pasa automaticamente a estado en progreso', function () {
    $this->actingAs($this->gestor);

    expect($this->gap->status)->toBe(Gap::STATUS_ABIERTO);

    $service = app(CorrectiveActionService::class);
    $service->createForGap($this->gap, [
        'description' => 'Acción de mitigación inmediata',
        'responsible_id' => $this->jefe->id,
        'due_date' => now()->addDays(7)->toDateString(),
    ], $this->gestor);

    $this->gap->refresh();

    expect($this->gap->status)->toBe(Gap::STATUS_EN_PROGRESO);

    $this->assertDatabaseHas('gap_logs', [
        'gap_id' => $this->gap->id,
        'field' => 'status',
        'old_value' => Gap::STATUS_ABIERTO,
        'new_value' => Gap::STATUS_EN_PROGRESO,
    ]);
});

test('no se puede crear una segunda accion correctiva mientras la primera del mismo gap siga sin cerrarse (server-side)', function () {
    $this->actingAs($this->gestor);

    $service = app(CorrectiveActionService::class);

    $service->createForGap($this->gap, [
        'description' => 'Primera acción correctiva activa',
        'responsible_id' => $this->colaborador->id,
        'due_date' => now()->addDays(10)->toDateString(),
    ], $this->gestor);

    expect(fn () => $service->createForGap($this->gap, [
        'description' => 'Segunda acción que debe ser rechazada',
        'responsible_id' => $this->jefe->id,
        'due_date' => now()->addDays(15)->toDateString(),
    ], $this->gestor))->toThrow(ValidationException::class);
});

test('crear accion correctiva mediante componente Livewire valida campos obligatorios y crea la accion', function () {
    $this->actingAs($this->gestor);

    Livewire::test(GapDetail::class, ['gap' => $this->gap])
        ->call('openCreateModal')
        ->set('description', '')
        ->set('responsible_id', null)
        ->set('due_date', '')
        ->call('createCorrectiveAction')
        ->assertHasErrors(['description', 'responsible_id', 'due_date']);

    Livewire::test(GapDetail::class, ['gap' => $this->gap])
        ->call('openCreateModal')
        ->set('description', 'Implementar plantilla oficial de cronograma')
        ->set('responsible_id', $this->colaborador->id)
        ->set('due_date', now()->addDays(10)->toDateString())
        ->call('createCorrectiveAction')
        ->assertHasNoErrors()
        ->assertSet('showingCreateModal', false);

    $this->gap->refresh();
    expect($this->gap->status)->toBe(Gap::STATUS_EN_PROGRESO)
        ->and($this->gap->hasActiveCorrectiveAction())->toBeTrue();
});

test('el responsable asignado puede ver la accion especifica y su gap aunque no tenga rol de gestion', function () {
    $service = app(CorrectiveActionService::class);
    $accion = $service->createForGap($this->gap, [
        'description' => 'Tarea asignada a colaborador externo al equipo de gestion',
        'responsible_id' => $this->colaboradorSinProyecto->id,
        'due_date' => now()->addDays(8)->toDateString(),
    ], $this->gestor);

    $this->actingAs($this->colaboradorSinProyecto);

    $this->get(route('gaps.show', $this->gap))
        ->assertOk()
        ->assertSee('Tarea asignada a colaborador externo al equipo de gestion')
        ->assertSee($this->colaboradorSinProyecto->name);
});

test('un jefe de proyecto o colaborador no puede crear una accion correctiva (403)', function () {
    $this->actingAs($this->jefe);

    Livewire::test(GapDetail::class, ['gap' => $this->gap])
        ->call('openCreateModal')
        ->assertForbidden();
});
