<?php

use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\Practice;
use App\Models\PracticeCriterion;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CmmiCatalogSeeder::class);
});

/**
 * @return array{0: Appraisal, 1: Practice}
 */
function setupCriterioScenario(): array
{
    $project = Project::create([
        'name' => 'Proyecto Criterios',
        'code' => 'P-CRI',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal Criterios',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $appraisal->practices()->sync(AppraisalScope::pivotFor([$practice->id]));

    return [$appraisal, $practice];
}

test('a gestor de procesos reaches the criteria of a practice in scope', function () {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('criterios.index', [$appraisal, $practice]))
        ->assertOk()
        ->assertSee('PLAN 1.1-C1')
        ->assertSee($appraisal->name)
        ->assertSee($practice->name);
});

test('a guest is redirected to login instead of reaching the criteria', function () {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->get(route('criterios.index', [$appraisal, $practice]))
        ->assertRedirect(route('login'));
});

test('roles without the evaluation capability receive 403 on the criteria', function (string $estado) {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->actingAs(User::factory()->{$estado}()->create());

    $this->get(route('criterios.index', [$appraisal, $practice]))
        ->assertForbidden();
})->with(['administrador', 'jefeProyecto', 'colaborador']);

test('a practice outside the appraisal scope returns 404', function () {
    [$appraisal] = setupCriterioScenario();
    $outside = Practice::where('code', 'PLAN 2.1')->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('criterios.index', [$appraisal, $outside]))
        ->assertNotFound();
});

test('a criterion of another practice returns 404 on edit', function () {
    [$appraisal, $practice] = setupCriterioScenario();
    $foreign = PracticeCriterion::whereRelation('practice', 'code', 'PLAN 2.1')->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('criterios.edit', [$appraisal, $practice, $foreign]))
        ->assertNotFound();
});

test('a gestor de procesos registers a criterion for the practice in context', function () {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->post(route('criterios.store', [$appraisal, $practice]), [
        'code' => 'PLAN 1.1-C9',
        'descripcion' => 'Existe acta de aprobación del cronograma firmada por el patrocinador.',
        'orden' => 3,
        'required' => '1',
        'estado' => '1',
    ])->assertRedirect(route('criterios.index', [$appraisal, $practice]));

    $criterio = PracticeCriterion::where('code', 'PLAN 1.1-C9')->firstOrFail();

    expect($criterio->practice_id)->toBe($practice->id)
        ->and($criterio->orden)->toBe(3)
        ->and($criterio->required)->toBeTrue()
        ->and($criterio->estado)->toBeTrue();
});

test('a duplicated code within the same practice is rejected', function () {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->post(route('criterios.store', [$appraisal, $practice]), [
        'code' => 'PLAN 1.1-C1',
        'descripcion' => 'Criterio duplicado.',
    ])->assertSessionHasErrors('code');
});

test('a criterion can be updated from its practice context', function () {
    [$appraisal, $practice] = setupCriterioScenario();
    $criterio = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->put(route('criterios.update', [$appraisal, $practice, $criterio]), [
        'code' => $criterio->code,
        'descripcion' => 'Descripción corregida por el Gestor de Procesos.',
        'orden' => 5,
    ])->assertRedirect(route('criterios.index', [$appraisal, $practice]));

    $criterio->refresh();

    expect($criterio->description)->toBe('Descripción corregida por el Gestor de Procesos.')
        ->and($criterio->orden)->toBe(5)
        ->and($criterio->estado)->toBeFalse();
});

test('a criterion can be deleted from its practice context', function () {
    [$appraisal, $practice] = setupCriterioScenario();
    $criterio = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->delete(route('criterios.destroy', [$appraisal, $practice, $criterio]))
        ->assertRedirect(route('criterios.index', [$appraisal, $practice]));

    expect(PracticeCriterion::find($criterio->id))->toBeNull();
});

test('roles without the evaluation capability cannot create criteria', function () {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->actingAs(User::factory()->administrador()->create());

    $this->post(route('criterios.store', [$appraisal, $practice]), [
        'code' => 'PLAN 1.1-C9',
        'descripcion' => 'Intento no autorizado.',
    ])->assertForbidden();

    expect(PracticeCriterion::where('code', 'PLAN 1.1-C9')->exists())->toBeFalse();
});

test('the practice list offers the entry point to manage criteria', function () {
    [$appraisal, $practice] = setupCriterioScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertOk()
        ->assertSee(route('criterios.index', [$appraisal->id, $practice->id]), false);
});
