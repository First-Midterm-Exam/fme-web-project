<?php

use App\Livewire\Appraisals\AppraisalPracticeList;
use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CmmiCatalogSeeder::class);
});

/**
 * @param  list<string>  $practiceCodes
 * @return array{0: User, 1: Project, 2: Appraisal}
 */
function setupAppraisalWithScope(array $practiceCodes = ['PLAN 1.1', 'PLAN 2.1', 'EST 1.1']): array
{
    $gestor = User::factory()->gestorProcesos()->create();
    $project = Project::create([
        'name' => 'Proyecto Core CMMI',
        'code' => 'P-CORE',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal CMMI Nivel 2',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => 'activo',
    ]);

    $practices = Practice::whereIn('code', $practiceCodes)->get();
    $appraisal->practices()->sync($practices->pluck('id')->all());

    foreach ($practices as $practice) {
        PracticeEvaluation::firstOrCreate([
            'appraisal_id' => $appraisal->id,
            'practice_id' => $practice->id,
        ], [
            'status' => PracticeEvaluation::STATUS_NO_EVALUADA,
        ]);
    }

    return [$gestor, $project, $appraisal];
}

test('displays only practices included in scope and excludes practices out of scope', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'PLAN 2.1', 'EST 1.1']);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertOk()
        ->assertSee('PLAN 1.1')
        ->assertSee('PLAN 2.1')
        ->assertSee('EST 1.1')
        ->assertDontSee('TS 1.1')
        ->assertDontSee('CM 1.1')
        ->assertDontSee('PQA 1.1')
        ->assertDontSee('VV 1.1');
});

test('practices are grouped by practice area', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'EST 1.1']);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertOk()
        ->assertSee('Planning')
        ->assertSee('Estimating')
        ->assertSee('PLAN')
        ->assertSee('EST');
});

test('filter by practice area reduces the list correctly', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'EST 1.1']);
    $planArea = PracticeArea::where('code', 'PLAN')->firstOrFail();
    $estArea = PracticeArea::where('code', 'EST')->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('areaFilter', $planArea->id)
        ->assertSee('PLAN 1.1')
        ->assertDontSee('EST 1.1');

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('areaFilter', $estArea->id)
        ->assertSee('EST 1.1')
        ->assertDontSee('PLAN 1.1');
});

test('filter by evaluation status filters practices correctly', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'PLAN 2.1']);

    $plan11 = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $plan21 = Practice::where('code', 'PLAN 2.1')->firstOrFail();

    PracticeEvaluation::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $plan11->id)
        ->update(['status' => PracticeEvaluation::STATUS_CUMPLE]);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('statusFilter', PracticeEvaluation::STATUS_CUMPLE)
        ->assertSee('PLAN 1.1')
        ->assertDontSee('PLAN 2.1');

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('statusFilter', PracticeEvaluation::STATUS_NO_EVALUADA)
        ->assertSee('PLAN 2.1')
        ->assertDontSee('PLAN 1.1');
});

test('detail modal displays preloaded criteria from HU-07', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);
    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->call('openDetailModal', $practice->id)
        ->assertSet('showDetailModal', true)
        ->assertSee('PLAN 1.1-C1')
        ->assertSee('PLAN 1.1-C2')
        ->assertSee('Obligatorio')
        ->assertSee('Existe un cronograma inicial con actividades, hitos y fechas tentativas aprobado.');
});

test('a practice in No evaluada status displays 0 percent completion', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertSee('0%');
});

test('displays evaluation status read from practice_evaluations', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'PLAN 2.1', 'EST 1.1']);

    $plan11 = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $plan21 = Practice::where('code', 'PLAN 2.1')->firstOrFail();
    $est11 = Practice::where('code', 'EST 1.1')->firstOrFail();

    PracticeEvaluation::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $plan11->id)
        ->update(['status' => PracticeEvaluation::STATUS_VERIFICADA]);

    PracticeEvaluation::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $plan21->id)
        ->update(['status' => PracticeEvaluation::STATUS_PARCIAL]);

    PracticeEvaluation::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $est11->id)
        ->update(['status' => PracticeEvaluation::STATUS_NO_CUMPLE]);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertSee('Verificada')
        ->assertSee('Parcial')
        ->assertSee('No cumple');
});

test('detail modal displays empty state for observations when none exist', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);
    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->call('openDetailModal', $practice->id)
        ->assertSee('Sin observaciones registradas');
});

test('detail modal displays empty state for evidences when none exist', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);
    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->call('openDetailModal', $practice->id)
        ->assertSee('No existen evidencias asociadas');
});

test('an unassigned user receives 403 when trying to access appraisal practices', function () {
    [$gestor, $project, $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);

    $unassignedPm = User::factory()->jefeProyecto()->create();
    $unassignedColaborador = User::factory()->colaborador()->create();

    $this->actingAs($unassignedPm);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertForbidden();

    $this->actingAs($unassignedColaborador);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertForbidden();

    $assignedPm = User::factory()->jefeProyecto()->create();
    $project->users()->attach($assignedPm->id);

    $this->actingAs($assignedPm);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertOk();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertOk();

    $this->actingAs($gestor);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertOk();
});

test('the component is strictly read-only and does not offer mutation actions for states or criteria', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);

    $this->actingAs($gestor);

    $component = Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertOk()
        ->assertDontSee('Guardar')
        ->assertDontSee('btn-save')
        ->assertDontSee('Eliminar');

    expect(method_exists($component->instance(), 'save'))->toBeFalse()
        ->and(method_exists($component->instance(), 'updateStatus'))->toBeFalse()
        ->and(method_exists($component->instance(), 'updateCriteria'))->toBeFalse();
});
