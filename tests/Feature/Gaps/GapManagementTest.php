<?php

use App\Livewire\Gaps\GapManagement;
use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\Gap;
use App\Models\GapLog;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use App\Policies\GapPolicy;
use App\Services\GapTransitionService;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        CmmiCatalogSeeder::class,
    ]);
});

/**
 * Helper para crear un escenario completo de prueba para gaps.
 *
 * @param  list<User>  $assignedUsers
 * @return array{0: Project, 1: Appraisal, 2: PracticeEvaluation, 3: Gap}
 */
function crearEscenarioGap(array $assignedUsers = [], string $status = Gap::STATUS_ABIERTO): array
{
    $project = Project::create([
        'name' => 'Proyecto Alfa Gaps',
        'code' => 'P-ALFA-'.rand(100, 999),
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    if (! empty($assignedUsers)) {
        $project->users()->attach(collect($assignedUsers)->pluck('id'));
    }

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal CMMI Nivel 2',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $appraisal->practices()->sync(AppraisalScope::pivotFor([$practice->id]));

    $evaluation = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $practice->id,
        'status' => PracticeEvaluation::STATUS_NO_CUMPLE,
    ]);

    $gap = Gap::create([
        'code' => Gap::generateNextCode(),
        'practice_evaluation_id' => $evaluation->id,
        'title' => 'Falta cronograma inicial',
        'description' => 'No se presentó cronograma de trabajo formal.',
        'severity' => Gap::SEVERITY_MEDIA,
        'status' => $status,
    ]);

    return [$project, $appraisal, $evaluation, $gap];
}

// =============================================================================
// 1. Editar severidad, responsable y fecha límite de un gap existente
// =============================================================================

test('gestor de procesos y administrador pueden editar severidad, responsable y fecha limite de un gap', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $responsable = User::factory()->colaborador()->create();
    [$project, $appraisal, $evaluation, $gap] = crearEscenarioGap();

    $this->actingAs($gestor);

    $fechaLimite = now()->addDays(15)->format('Y-m-d');

    Livewire::test(GapManagement::class)
        ->call('openEdit', $gap->id)
        ->set('title', 'Cronograma inicial desactualizado')
        ->set('severity', Gap::SEVERITY_ALTA)
        ->set('assignedToId', $responsable->id)
        ->set('dueDate', $fechaLimite)
        ->set('status', Gap::STATUS_EN_PROGRESO)
        ->call('save')
        ->assertHasNoErrors();

    $gap->refresh();

    expect($gap->title)->toBe('Cronograma inicial desactualizado')
        ->and($gap->severity)->toBe(Gap::SEVERITY_ALTA)
        ->and($gap->assigned_to_id)->toBe($responsable->id)
        ->and($gap->due_date?->format('Y-m-d'))->toBe($fechaLimite)
        ->and($gap->status)->toBe(Gap::STATUS_EN_PROGRESO);
});

// =============================================================================
// 2. Los cambios de severidad, responsable, fecha y estado quedan en bitácora (RNF-06)
// =============================================================================

test('cambios de severidad, responsable, fecha y estado quedan registrados en la bitacora RNF-06', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $responsable = User::factory()->colaborador()->create();
    [$project, $appraisal, $evaluation, $gap] = crearEscenarioGap();

    $service = app(GapTransitionService::class);

    // Actualizar datos mediante el servicio
    $service->updateGap($gap, [
        'severity' => Gap::SEVERITY_CRITICA,
        'assigned_to_id' => $responsable->id,
        'due_date' => '2026-10-30',
        'status' => Gap::STATUS_EN_PROGRESO,
    ], $gestor);

    // Verificar que la bitácora contiene los registros correspondientes
    $logs = GapLog::where('gap_id', $gap->id)->get();

    expect($logs->where('field', 'severity')->first())->not->toBeNull()
        ->and($logs->where('field', 'severity')->first()->old_value)->toBe(Gap::SEVERITY_MEDIA)
        ->and($logs->where('field', 'severity')->first()->new_value)->toBe(Gap::SEVERITY_CRITICA)
        ->and($logs->where('field', 'assigned_to_id')->first())->not->toBeNull()
        ->and($logs->where('field', 'assigned_to_id')->first()->new_value)->toBe((string) $responsable->id)
        ->and($logs->where('field', 'due_date')->first())->not->toBeNull()
        ->and($logs->where('field', 'status')->first())->not->toBeNull()
        ->and($logs->where('field', 'status')->first()->old_value)->toBe(Gap::STATUS_ABIERTO)
        ->and($logs->where('field', 'status')->first()->new_value)->toBe(Gap::STATUS_EN_PROGRESO);

    // La relación en el modelo Gap retorna la bitácora
    expect($gap->bitacora)->toHaveCount(4)
        ->and($gap->logs)->toHaveCount(4);
});

// =============================================================================
// 3. Filtrar listado de gaps por severidad y por estado
// =============================================================================

test('el listado de gaps se puede filtrar por severidad y por estado', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    [$project, $appraisal, $evaluation, $gap1] = crearEscenarioGap();

    $gap2 = Gap::create([
        'code' => Gap::generateNextCode(),
        'practice_evaluation_id' => $evaluation->id,
        'title' => 'Gap Critico',
        'severity' => Gap::SEVERITY_CRITICA,
        'status' => Gap::STATUS_EN_PROGRESO,
    ]);

    $gap3 = Gap::create([
        'code' => Gap::generateNextCode(),
        'practice_evaluation_id' => $evaluation->id,
        'title' => 'Gap Resuelto',
        'severity' => Gap::SEVERITY_BAJA,
        'status' => Gap::STATUS_RESUELTO,
    ]);

    $this->actingAs($gestor);

    // Filtro por severidad Crítica
    Livewire::test(GapManagement::class)
        ->set('severityFilter', Gap::SEVERITY_CRITICA)
        ->assertSee($gap2->code)
        ->assertDontSee($gap1->code)
        ->assertDontSee($gap3->code);

    // Filtro por estado Resuelto
    Livewire::test(GapManagement::class)
        ->set('statusFilter', Gap::STATUS_RESUELTO)
        ->assertSee($gap3->code)
        ->assertDontSee($gap1->code)
        ->assertDontSee($gap2->code);
});

// =============================================================================
// 4. Gap con fecha límite vencida se resalta visualmente (RF-30)
// =============================================================================

test('un gap con fecha limite vencida y sin resolver se marca visualmente como alerta RF-30', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    [$project, $appraisal, $evaluation, $gap] = crearEscenarioGap();

    // Establecer fecha vencida
    $gap->update([
        'due_date' => now()->subDays(3)->format('Y-m-d'),
        'status' => Gap::STATUS_ABIERTO,
    ]);

    expect($gap->isOverdue())->toBeTrue();

    $this->actingAs($gestor);

    Livewire::test(GapManagement::class)
        ->assertSee('¡Vencido!')
        ->assertSee('table-danger');

    // Al resolverse, deja de considerarse vencido como alerta activa
    $gap->update(['status' => Gap::STATUS_RESUELTO]);
    expect($gap->isOverdue())->toBeFalse();
});

// =============================================================================
// 5. Un Jefe de Proyecto o Colaborador solo ve los gaps de sus proyectos y no puede editarlos
// =============================================================================

test('jefe de proyecto solo ve gaps de sus proyectos asignados y recibe 403 al intentar editar', function (): void {
    $pmAsignado = User::factory()->jefeProyecto()->create();
    $pmAjeno = User::factory()->jefeProyecto()->create();

    [$project, $appraisal, $evaluation, $gap] = crearEscenarioGap([$pmAsignado]);

    $policy = new GapPolicy;

    // PM Asignado puede ver pero no editar
    expect($policy->view($pmAsignado, $gap))->toBeTrue()
        ->and($policy->update($pmAsignado, $gap))->toBeFalse();

    // PM Ajeno no puede ver ni editar
    expect($policy->view($pmAjeno, $gap))->toBeFalse()
        ->and($policy->update($pmAjeno, $gap))->toBeFalse();

    // En Livewire, el PM Asignado puede ver el componente pero no ve el botón de edición
    $this->actingAs($pmAsignado);
    Livewire::test(GapManagement::class)
        ->assertOk()
        ->assertSee($gap->code)
        ->assertDontSee('btn-edit-gap-'.$gap->id);

    // Si intenta invocar openEdit o save directamente, recibe 403
    Livewire::test(GapManagement::class)
        ->call('openEdit', $gap->id)
        ->assertForbidden();

    // El PM Ajeno no ve el gap en su listado
    $this->actingAs($pmAjeno);
    Livewire::test(GapManagement::class)
        ->assertOk()
        ->assertDontSee($gap->code);
});

// =============================================================================
// 6. No se puede cambiar el estado saltándose pasos del ciclo de vida (RF-29)
// =============================================================================

test('no se puede cambiar el estado del gap saltandose pasos del ciclo de vida RF-29', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    [$project, $appraisal, $evaluation, $gap] = crearEscenarioGap([], Gap::STATUS_ABIERTO);

    $this->actingAs($gestor);

    // Intentar saltar de "Abierto" directamente a "Verificado" falla en Livewire con error de validación
    Livewire::test(GapManagement::class)
        ->call('openEdit', $gap->id)
        ->set('status', Gap::STATUS_VERIFICADO)
        ->call('save')
        ->assertHasErrors(['status']);

    expect($gap->fresh()->status)->toBe(Gap::STATUS_ABIERTO);

    // A nivel de servicio o modelo, arroja DomainException
    $service = app(GapTransitionService::class);
    expect(fn () => $service->transition($gap, Gap::STATUS_VERIFICADO, $gestor))
        ->toThrow(DomainException::class);

    // Transición válida: Abierto -> En progreso -> Resuelto -> Verificado
    $service->transition($gap, Gap::STATUS_EN_PROGRESO, $gestor);
    expect($gap->fresh()->status)->toBe(Gap::STATUS_EN_PROGRESO);

    $service->transition($gap, Gap::STATUS_RESUELTO, $gestor);
    expect($gap->fresh()->status)->toBe(Gap::STATUS_RESUELTO);

    $service->transition($gap, Gap::STATUS_VERIFICADO, $gestor);
    expect($gap->fresh()->status)->toBe(Gap::STATUS_VERIFICADO);
});
