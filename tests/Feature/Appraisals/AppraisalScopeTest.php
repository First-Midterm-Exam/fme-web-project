<?php

use App\Livewire\Appraisals\AppraisalScopeSelection;
use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeAssessment;
use App\Models\Project;
use App\Models\User;
use App\Policies\AppraisalScopePolicy;
use App\Services\AppraisalScopeService;
use App\Services\AppraisalStateService;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CmmiCatalogSeeder::class);
});

// =============================================================================
// a) Seleccionar un área marca todas sus prácticas
// =============================================================================

test('selecting a practice area selects all of its practices in the scope component', function () {
    $gestor = User::factory()->gestorProcesos()->create();
    $project = Project::create([
        'name' => 'Proyecto CMMI',
        'code' => 'P-CMMI',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal Borrador',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'borrador',
    ]);

    $area = PracticeArea::where('code', 'PLAN')->firstOrFail();
    $expectedPracticeIds = $area->practices->pluck('id')->toArray();

    $this->actingAs($gestor);

    $component = Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('toggleArea', $area->id);

    // Verify all practices of the area are in selectedPracticeIds
    foreach ($expectedPracticeIds as $practiceId) {
        expect($component->get('selectedPracticeIds'))->toContain($practiceId);
    }

    // Now deselecting the area should remove all its practices
    $component->call('toggleArea', $area->id);
    foreach ($expectedPracticeIds as $practiceId) {
        expect($component->get('selectedPracticeIds'))->not->toContain($practiceId);
    }
});

// =============================================================================
// b) Activar el appraisal genera exactamente una evaluación por cada práctica en el alcance, en estado "No evaluada"
// =============================================================================

test('activating the appraisal generates exactly one assessment in No evaluada status for each practice in scope', function () {
    $gestor = User::factory()->gestorProcesos()->create();
    $project = Project::create([
        'name' => 'Proyecto Evaluación',
        'code' => 'P-EVAL',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal para Activar',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'borrador',
    ]);

    // Attach 5 practices to the scope
    $practices = Practice::take(5)->get();
    $appraisal->practices()->sync($practices->pluck('id')->toArray());

    expect(PracticeAssessment::where('appraisal_id', $appraisal->id)->count())->toBe(0);

    // Activate using service
    $stateService = app(AppraisalStateService::class);
    $stateService->activate($appraisal);

    $appraisal->refresh();
    expect($appraisal->status)->toBe('activo');

    // Verify exactly one assessment per practice in scope, in 'No evaluada' status
    $assessments = PracticeAssessment::where('appraisal_id', $appraisal->id)->get();
    expect($assessments)->toHaveCount(5);

    foreach ($assessments as $assessment) {
        expect($assessment->status)->toBe('No evaluada')
            ->and($practices->pluck('id')->toArray())->toContain($assessment->practice_id);
    }
});

// =============================================================================
// c) Con el appraisal activo, la pantalla de alcance no permite cambios (ni por UI ni por request directo), para ningún rol
// =============================================================================

test('with an active appraisal, scope cannot be modified by any role via UI or direct request', function () {
    $admin = User::factory()->administrador()->create();
    $gestor = User::factory()->gestorProcesos()->create();

    $project = Project::create([
        'name' => 'Proyecto Activo',
        'code' => 'P-ACT',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal Ya Activo',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'activo',
    ]);

    $policy = new AppraisalScopePolicy;
    expect($policy->update($admin, $appraisal))->toBeFalse()
        ->and($policy->update($gestor, $appraisal))->toBeFalse();

    // Admin attempt via Livewire
    $this->actingAs($admin);
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('togglePractice', 1)
        ->assertForbidden();

    // Gestor attempt via Livewire
    $this->actingAs($gestor);
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('save')
        ->assertForbidden();

    // Direct service attempt throws DomainException
    $scopeService = app(AppraisalScopeService::class);
    expect(fn () => $scopeService->syncScope($appraisal, [1, 2]))
        ->toThrow(DomainException::class);
});

// =============================================================================
// d) Un Jefe de Proyecto ve el alcance de su proyecto en modo lectura, sin controles de edición
// =============================================================================

test('project manager sees the scope of their assigned project in read-only mode', function () {
    $pm = User::factory()->jefeProyecto()->create();

    $project = Project::create([
        'name' => 'Proyecto PM Asignado',
        'code' => 'P-PM-ASIG',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $project->users()->attach($pm->id);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal PM',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => 'borrador',
    ]);

    $this->actingAs($pm);

    $policy = new AppraisalScopePolicy;
    expect($policy->view($pm, $appraisal))->toBeTrue()
        ->and($policy->update($pm, $appraisal))->toBeFalse();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->assertOk()
        ->assertSee('Modo Lectura')
        ->assertDontSee('btn-save-scope-header')
        ->assertDontSee('btn-select-all');
});

// =============================================================================
// e) Un usuario recibe 403 al acceder al alcance de un appraisal de un proyecto ajeno
// =============================================================================

test('a user receives 403 when accessing the scope of an appraisal of an unassigned project', function () {
    $pm = User::factory()->jefeProyecto()->create();
    $colaborador = User::factory()->colaborador()->create();

    $unassignedProject = Project::create([
        'name' => 'Proyecto Sin Asignar',
        'code' => 'P-UNASSIGNED',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $unassignedProject->id,
        'name' => 'Appraisal Ajeno',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'borrador',
    ]);

    // PM accessing unassigned project appraisal scope
    $this->actingAs($pm);
    $this->get(route('appraisals.scope', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->assertForbidden();

    // Colaborador accessing unassigned project appraisal scope
    $this->actingAs($colaborador);
    $this->get(route('appraisals.scope', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->assertForbidden();
});

// =============================================================================
// f) Un Jefe de Proyecto que intenta editar el alcance por request directo recibe 403
// =============================================================================

test('project manager trying to modify scope via direct request receives 403 even in draft status', function () {
    $pm = User::factory()->jefeProyecto()->create();

    $project = Project::create([
        'name' => 'Proyecto Asignado PM',
        'code' => 'P-ASIG',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $project->users()->attach($pm->id);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal en Borrador',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'borrador',
    ]);

    $this->actingAs($pm);

    // Livewire togglePractice forbidden
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('togglePractice', 1)
        ->assertForbidden();

    // Livewire toggleArea forbidden
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('toggleArea', 1)
        ->assertForbidden();

    // Livewire save forbidden
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('save')
        ->assertForbidden();
});
