<?php

use App\Livewire\Appraisals\AppraisalScopeSelection;
use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeEvaluation;
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

    foreach ($expectedPracticeIds as $practiceId) {
        expect($component->get('selectedPracticeIds'))->toContain($practiceId);
    }

    $component->call('toggleArea', $area->id);
    foreach ($expectedPracticeIds as $practiceId) {
        expect($component->get('selectedPracticeIds'))->not->toContain($practiceId);
    }
});

test('activating the appraisal generates exactly one evaluation in No evaluada status for each practice in scope', function () {
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

    $practices = Practice::take(5)->get();
    $appraisal->practices()->sync($practices->pluck('id')->toArray());

    expect(PracticeEvaluation::where('appraisal_id', $appraisal->id)->count())->toBe(0);

    $stateService = app(AppraisalStateService::class);
    $stateService->activate($appraisal);

    $appraisal->refresh();
    expect($appraisal->status)->toBe('activo');

    $evaluations = PracticeEvaluation::where('appraisal_id', $appraisal->id)->get();
    expect($evaluations)->toHaveCount(5);

    foreach ($evaluations as $evaluation) {
        expect($evaluation->status)->toBe('No evaluada')
            ->and($practices->pluck('id')->toArray())->toContain($evaluation->practice_id);
    }
});

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

    $this->actingAs($admin);
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('togglePractice', 1)
        ->assertForbidden();

    $this->actingAs($gestor);
    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('save')
        ->assertForbidden();

    $scopeService = app(AppraisalScopeService::class);
    expect(fn () => $scopeService->syncScope($appraisal, [1, 2]))
        ->toThrow(DomainException::class);
});

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

    $this->actingAs($pm);
    $this->get(route('appraisals.scope', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->assertForbidden();

    $this->actingAs($colaborador);
    $this->get(route('appraisals.scope', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->assertForbidden();
});

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

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('togglePractice', 1)
        ->assertForbidden();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('toggleArea', 1)
        ->assertForbidden();

    Livewire::test(AppraisalScopeSelection::class, ['appraisal' => $appraisal])
        ->call('save')
        ->assertForbidden();
});
