<?php

use App\Livewire\Appraisals\AppraisalPracticeList;
use App\Livewire\Appraisals\PracticeChecklist;
use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Practice;
use App\Models\PracticeCriterion;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CmmiCatalogSeeder::class);
});

/**
 * @return array{0: Appraisal, 1: Practice, 2: Project}
 */
function setupChecklistScenario(string $status = Appraisal::STATUS_ACTIVO): array
{
    $project = Project::create([
        'name' => 'Proyecto Checklist',
        'code' => 'P-CHK',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal Checklist',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => $status,
    ]);

    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $appraisal->practices()->sync([$practice->id]);

    PracticeEvaluation::firstOrCreate([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $practice->id,
    ], [
        'status' => PracticeEvaluation::STATUS_NO_EVALUADA,
    ]);

    return [$appraisal, $practice, $project];
}

test('a gestor de procesos can open the checklist of a practice in scope', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practice->id]))
        ->assertOk()
        ->assertSee('PLAN 1.1-C1')
        ->assertSee('PLAN 1.1-C2');
});

test('a guest is redirected to login instead of receiving 403', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practice->id]))
        ->assertRedirect(route('login'));
});

test('a practice outside the appraisal scope returns 404', function () {
    [$appraisal] = setupChecklistScenario();
    $outside = Practice::where('code', 'PLAN 2.1')->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $outside->id]))
        ->assertNotFound();
});

test('marking a criterion stores the status with its evaluator and timestamp', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $gestor = User::factory()->gestorProcesos()->create();
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criterion->id, CriterionCheck::STATUS_CUMPLE)
        ->assertOk();

    $mark = CriterionCheck::where('practice_criterion_id', $criterion->id)->firstOrFail();

    expect($mark->status)->toBe(CriterionCheck::STATUS_CUMPLE)
        ->and($mark->evaluated_by)->toBe($gestor->id)
        ->and($mark->evaluated_at)->not->toBeNull();
});

test('marking the same criterion twice updates the mark instead of duplicating it', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criterion->id, CriterionCheck::STATUS_CUMPLE)
        ->call('markCriterion', $criterion->id, CriterionCheck::STATUS_NO_CUMPLE);

    expect(CriterionCheck::where('practice_criterion_id', $criterion->id)->count())->toBe(1)
        ->and(CriterionCheck::where('practice_criterion_id', $criterion->id)->value('status'))
        ->toBe(CriterionCheck::STATUS_NO_CUMPLE);
});

test('the compliance percentage reflects the criteria marked as met', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criteria = $practice->criteria;

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criteria[0]->id, CriterionCheck::STATUS_CUMPLE)
        ->assertSee('50%');
});

test('a criterion marked as no aplica leaves the compliance denominator', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criteria = $practice->criteria;

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criteria[0]->id, CriterionCheck::STATUS_CUMPLE)
        ->call('markCriterion', $criteria[1]->id, CriterionCheck::STATUS_NO_APLICA)
        ->assertSee('100%');
});

test('marking every criterion at once applies the status to all of them', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markAll', CriterionCheck::STATUS_CUMPLE)
        ->assertSee('100%');

    expect(CriterionCheck::where('status', CriterionCheck::STATUS_CUMPLE)->count())
        ->toBe($practice->criteria->count());
});

test('inactive criteria are excluded from the checklist', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();
    $criterion->update(['estado' => false]);

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->assertDontSee($criterion->code)
        ->assertSee('PLAN 1.1-C2');
});

test('an observation can be attached to a criterion mark', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('editNote', $criterion->id)
        ->set('notes.'.$criterion->id, 'El cronograma fue aprobado en el acta del 12 de marzo.')
        ->call('saveNote', $criterion->id)
        ->assertHasNoErrors();

    expect(CriterionCheck::where('practice_criterion_id', $criterion->id)->value('notes'))
        ->toBe('El cronograma fue aprobado en el acta del 12 de marzo.');
});

test('an observation longer than 1000 characters is rejected', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('editNote', $criterion->id)
        ->set('notes.'.$criterion->id, str_repeat('a', 1001))
        ->call('saveNote', $criterion->id)
        ->assertHasErrors('notes.'.$criterion->id);
});

test('an administrador can consult the checklist but cannot mark criteria', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->administrador()->create());

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practice->id]))
        ->assertOk();

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criterion->id, CriterionCheck::STATUS_CUMPLE)
        ->assertForbidden();

    expect(CriterionCheck::count())->toBe(0);
});

test('a jefe de proyecto assigned to the project can consult but cannot mark criteria', function () {
    [$appraisal, $practice, $project] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();
    $jefe = User::factory()->jefeProyecto()->create();
    $project->users()->attach($jefe->id);

    $this->actingAs($jefe);

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practice->id]))
        ->assertOk()
        ->assertSee('Modo Consulta');

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criterion->id, CriterionCheck::STATUS_CUMPLE)
        ->assertForbidden();
});

test('a colaborador outside the project receives 403 on direct url access', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $this->actingAs(User::factory()->colaborador()->create());

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practice->id]))
        ->assertForbidden();
});

test('criteria cannot be marked on a closed appraisal', function () {
    [$appraisal, $practice] = setupChecklistScenario(Appraisal::STATUS_CERRADO);
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criterion->id, CriterionCheck::STATUS_CUMPLE)
        ->assertForbidden();

    expect(CriterionCheck::count())->toBe(0);
});

test('a criterion belonging to another practice is ignored', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $foreign = PracticeCriterion::whereRelation('practice', 'code', 'PLAN 2.1')->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $foreign->id, CriterionCheck::STATUS_CUMPLE);

    expect(CriterionCheck::count())->toBe(0);
});

test('an unknown status is rejected', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterion = $practice->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criterion->id, 'Aprobado con reservas');

    expect(CriterionCheck::count())->toBe(0);
});

function estadoDeLaPractica(Appraisal $appraisal, Practice $practice): string
{
    return PracticeEvaluation::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $practice->id)
        ->value('status');
}

test('marking criteria recalculates the practice status', function (array $marcas, string $esperado) {
    [$appraisal, $practice] = setupChecklistScenario();
    $criterios = $practice->criteria->values();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $componente = Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice]);

    foreach ($marcas as $indice => $estado) {
        $componente->call('markCriterion', $criterios[$indice]->id, $estado);
    }

    expect(estadoDeLaPractica($appraisal, $practice))->toBe($esperado);
})->with([
    'un obligatorio cumplido y otro pendiente' => [[0 => CriterionCheck::STATUS_CUMPLE], PracticeEvaluation::STATUS_PARCIAL],
    'todos los obligatorios cumplidos' => [[0 => CriterionCheck::STATUS_CUMPLE, 1 => CriterionCheck::STATUS_CUMPLE], PracticeEvaluation::STATUS_CUMPLE],
    'ninguno cumplido' => [[0 => CriterionCheck::STATUS_NO_CUMPLE, 1 => CriterionCheck::STATUS_NO_CUMPLE], PracticeEvaluation::STATUS_NO_CUMPLE],
    'uno cumplido y otro no cumplido' => [[0 => CriterionCheck::STATUS_CUMPLE, 1 => CriterionCheck::STATUS_NO_CUMPLE], PracticeEvaluation::STATUS_PARCIAL],
    'uno cumplido y otro no aplica' => [[0 => CriterionCheck::STATUS_CUMPLE, 1 => CriterionCheck::STATUS_NO_APLICA], PracticeEvaluation::STATUS_CUMPLE],
    'vuelto a pendiente' => [[0 => CriterionCheck::STATUS_CUMPLE, 1 => CriterionCheck::STATUS_PENDIENTE], PracticeEvaluation::STATUS_PARCIAL],
]);

test('marking every criterion at once recalculates the practice status', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markAll', CriterionCheck::STATUS_CUMPLE)
        ->assertSee(PracticeEvaluation::STATUS_CUMPLE);

    expect(estadoDeLaPractica($appraisal, $practice))->toBe(PracticeEvaluation::STATUS_CUMPLE);
});

test('inactive criteria do not count for the practice status', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    [$primero, $segundo] = $practice->criteria->values()->all();
    $segundo->update(['estado' => false]);

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $primero->id, CriterionCheck::STATUS_CUMPLE);

    expect(estadoDeLaPractica($appraisal, $practice))->toBe(PracticeEvaluation::STATUS_CUMPLE);
});

test('only verified evidences of the appraisal project make the practice verified', function (bool $delMismoProyecto, string $esperado) {
    [$appraisal, $practice, $project] = setupChecklistScenario();
    $gestor = User::factory()->gestorProcesos()->create();
    $this->seed(EvidenceStatusSeeder::class);

    $proyectoEvidencia = $delMismoProyecto ? $project : Project::create([
        'name' => 'Otro proyecto',
        'code' => 'P-OTRO',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $evidencia = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $proyectoEvidencia->id,
        'name' => 'Plan aprobado',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::VERIFICADA,
        'uploaded_by' => $gestor->id,
    ]);
    $evidencia->practices()->attach($practice->id);

    $this->actingAs($gestor);

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markAll', CriterionCheck::STATUS_CUMPLE);

    expect(estadoDeLaPractica($appraisal, $practice))->toBe($esperado);
})->with([
    'evidencia verificada del proyecto' => [true, PracticeEvaluation::STATUS_VERIFICADA],
    'evidencia verificada de otro proyecto' => [false, PracticeEvaluation::STATUS_CUMPLE],
]);

test('opening the checklist in read only mode does not change the practice status', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $this->actingAs(User::factory()->administrador()->create());

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practice->id]))->assertOk();

    expect(estadoDeLaPractica($appraisal, $practice))->toBe(PracticeEvaluation::STATUS_NO_EVALUADA);
});

test('the practice list shows the real compliance percentage of the checklist', function () {
    [$appraisal, $practice] = setupChecklistScenario();
    $criteria = $practice->criteria;

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practice])
        ->call('markCriterion', $criteria[0]->id, CriterionCheck::STATUS_CUMPLE);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertSee('50%');
});

test('the scope entry stores the practice area of each practice in scope', function () {
    [$appraisal, $practice] = setupChecklistScenario();

    $scope = AppraisalScope::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $practice->id)
        ->firstOrFail();

    expect($scope->practice_area_id)->toBe($practice->practice_area_id)
        ->and($scope->incluida)->toBeTrue();
});
