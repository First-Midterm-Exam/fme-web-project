<?php

use App\Livewire\Appraisals\AppraisalPracticeList;
use App\Livewire\Reports\Traceability;
use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\CorrectiveAction;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use App\Services\TraceabilityService;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(CmmiCatalogSeeder::class);
    $this->seed(EvidenceStatusSeeder::class);
});

/**
 * @return array{0: Appraisal, 1: Project, 2: Practice, 3: User}
 */
function escenarioTrazabilidad(): array
{
    $jefe = User::factory()->jefeProyecto()->create(['name' => 'Laura Jefa']);

    $proyecto = Project::create(['name' => 'Proyecto Trazable', 'code' => 'P-TRZ', 'start_date' => '2026-01-01', 'status' => 'activo']);
    $proyecto->users()->attach($jefe->id);

    $appraisal = Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => 'Appraisal Trazable',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $practicas = Practice::whereIn('code', ['PLAN 1.1', 'EST 1.1'])->get();
    $appraisal->practices()->sync(AppraisalScope::pivotFor($practicas->pluck('id')));

    $plan = $practicas->firstWhere('code', 'PLAN 1.1');
    $evaluacion = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $plan->id,
        'status' => PracticeEvaluation::STATUS_PARCIAL,
    ]);

    PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $practicas->firstWhere('code', 'EST 1.1')->id,
        'status' => PracticeEvaluation::STATUS_NO_EVALUADA,
    ]);

    [$primero, $segundo] = $plan->criteria->values()->all();

    CriterionCheck::create(['practice_evaluation_id' => $evaluacion->id, 'practice_criterion_id' => $primero->id, 'status' => CriterionCheck::STATUS_CUMPLE]);
    CriterionCheck::create([
        'practice_evaluation_id' => $evaluacion->id,
        'practice_criterion_id' => $segundo->id,
        'status' => CriterionCheck::STATUS_NO_CUMPLE,
        'notes' => 'Faltan los roles del equipo.',
    ]);

    $gapCriterio = Gap::create([
        'code' => 'GAP-0001',
        'practice_evaluation_id' => $evaluacion->id,
        'practice_criterion_id' => $segundo->id,
        'title' => 'Criterio '.$segundo->code.' no cumplido',
        'status' => Gap::STATUS_EN_PROGRESO,
        'severity' => Gap::SEVERITY_ALTA,
        'assigned_to_id' => $jefe->id,
        'due_date' => '2026-10-15',
    ]);

    CorrectiveAction::create([
        'gap_id' => $gapCriterio->id,
        'description' => 'Completar la matriz RACI del equipo',
        'responsible_id' => $jefe->id,
        'due_date' => '2026-10-10',
        'progress_percent' => 40,
        'status' => CorrectiveAction::STATUS_EN_PROGRESO,
    ]);

    $rechazada = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $proyecto->id,
        'name' => 'Cronograma sin firmas',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::RECHAZADA,
        'uploaded_by' => $jefe->id,
        'verification_reason' => 'El cronograma no está aprobado.',
        'verified_by' => $jefe->id,
        'verified_at' => now(),
    ]);
    $rechazada->practices()->attach($plan->id);

    Gap::create([
        'code' => 'GAP-0002',
        'practice_evaluation_id' => $evaluacion->id,
        'evidence_id' => $rechazada->id,
        'title' => 'Evidencia EV-0001 rechazada',
        'status' => Gap::STATUS_ABIERTO,
    ]);

    $verificada = Evidence::create([
        'code' => 'EV-0002',
        'project_id' => $proyecto->id,
        'name' => 'Acta de inicio firmada',
        'type' => Evidence::TYPE_ACTA,
        'status_id' => EvidenceStatus::VERIFICADA,
        'uploaded_by' => $jefe->id,
    ]);
    $verificada->practices()->attach($plan->id);

    $otroProyecto = Project::create(['name' => 'Otro', 'code' => 'P-OTR', 'start_date' => '2026-01-01', 'status' => 'activo']);
    $ajena = Evidence::create([
        'code' => 'EV-0003',
        'project_id' => $otroProyecto->id,
        'name' => 'Evidencia de otro proyecto',
        'type' => Evidence::TYPE_OTRO,
        'status_id' => EvidenceStatus::VERIFICADA,
        'uploaded_by' => $jefe->id,
    ]);
    $ajena->practices()->attach($plan->id);

    return [$appraisal, $proyecto, $plan, $jefe];
}

test('a gestor de procesos sees the full chain of a practice on one screen', function () {
    [$appraisal, , $plan] = escenarioTrazabilidad();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('readiness.trazabilidad', ['appraisal' => $appraisal->id, 'practica' => $plan->id]))
        ->assertOk()
        ->assertSeeInOrder([
            'PLAN 1.1-C2', 'No cumple', 'Faltan los roles del equipo.',
            'GAP-0001', 'En progreso', 'Alta', 'Laura Jefa',
            'Completar la matriz RACI del equipo',
        ])
        ->assertSeeInOrder([
            'EV-0001', 'Cronograma sin firmas', 'Rechazado', 'El cronograma no está aprobado.',
            'GAP-0002', 'Sin acción correctiva registrada.',
        ])
        ->assertSee('EV-0002')
        ->assertDontSee('EV-0003');
});

test('the practice list summarizes each practice of the appraisal scope', function () {
    [$appraisal] = escenarioTrazabilidad();

    $filas = app(TraceabilityService::class)->summary($appraisal)->keyBy(fn (array $fila): string => $fila['practice']->code);

    expect($filas->keys()->all())->toBe(['EST 1.1', 'PLAN 1.1'])
        ->and($filas['PLAN 1.1']['status'])->toBe(PracticeEvaluation::STATUS_PARCIAL)
        ->and($filas['PLAN 1.1']['compliance'])->toBe(50)
        ->and($filas['PLAN 1.1']['evidences'])->toBe(2)
        ->and($filas['PLAN 1.1']['verified_evidences'])->toBe(1)
        ->and($filas['PLAN 1.1']['open_gaps'])->toBe(2)
        ->and($filas['PLAN 1.1']['active_actions'])->toBe(1)
        ->and($filas['EST 1.1']['open_gaps'])->toBe(0);
});

test('selecting a practice loads its chain', function () {
    [$appraisal, , $plan] = escenarioTrazabilidad();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(Traceability::class)
        ->set('appraisalId', $appraisal->id)
        ->assertSee('Selecciona una práctica')
        ->call('selectPractice', $plan->id)
        ->assertSet('practiceId', $plan->id)
        ->assertSee('Completar la matriz RACI del equipo');
});

test('changing the appraisal clears the selected practice', function () {
    [$appraisal, , $plan] = escenarioTrazabilidad();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(Traceability::class)
        ->set('appraisalId', $appraisal->id)
        ->call('selectPractice', $plan->id)
        ->set('appraisalId', $appraisal->id)
        ->assertSet('practiceId', null);
});

test('the practice list can be searched by code or name', function () {
    [$appraisal] = escenarioTrazabilidad();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(Traceability::class)
        ->set('appraisalId', $appraisal->id)
        ->set('search', 'est 1')
        ->assertSee('EST 1.1')
        ->assertDontSee('Desarrollar el plan de trabajo básico del proyecto');
});

test('the most recent active appraisal is preselected', function () {
    [$appraisal] = escenarioTrazabilidad();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(Traceability::class)->assertSet('appraisalId', $appraisal->id);
});

test('a jefe de proyecto only reaches the traceability of assigned projects', function () {
    [$appraisal, , $plan, $jefe] = escenarioTrazabilidad();
    $ajeno = User::factory()->jefeProyecto()->create();

    $this->actingAs($jefe);
    $this->get(route('readiness.trazabilidad', ['appraisal' => $appraisal->id, 'practica' => $plan->id]))
        ->assertOk()
        ->assertSee('GAP-0001');

    app('auth')->forgetGuards();

    $this->actingAs($ajeno);
    $this->get(route('readiness.trazabilidad', ['appraisal' => $appraisal->id]))->assertForbidden();
});

test('roles outside the use case receive 403', function (string $estado) {
    escenarioTrazabilidad();

    $this->actingAs(User::factory()->{$estado}()->create());

    $this->get(route('readiness.trazabilidad'))->assertForbidden();
})->with(['administrador', 'colaborador']);

test('a guest is redirected to login', function () {
    $this->get(route('readiness.trazabilidad'))->assertRedirect(route('login'));
});

test('a user without appraisals sees an empty state', function () {
    $this->actingAs(User::factory()->jefeProyecto()->create());

    $this->get(route('readiness.trazabilidad'))
        ->assertOk()
        ->assertSee('Sin appraisals para consultar');
});

test('gaps without origin are still shown in the chain', function () {
    [$appraisal, , $plan] = escenarioTrazabilidad();

    Gap::create([
        'code' => 'GAP-0099',
        'practice_evaluation_id' => PracticeEvaluation::where('practice_id', $plan->id)->value('id'),
        'title' => 'Gap sin origen',
        'status' => Gap::STATUS_ABIERTO,
    ]);

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('readiness.trazabilidad', ['appraisal' => $appraisal->id, 'practica' => $plan->id]))
        ->assertOk()
        ->assertSee('Otros gaps de la práctica')
        ->assertSee('GAP-0099');
});

test('the menu and the practice detail link to the traceability screen', function () {
    [$appraisal] = escenarioTrazabilidad();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('dashboard'))->assertSee(route('readiness.trazabilidad'), false);

    $plan = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->call('openDetailModal', $plan->id)
        ->assertSee('Ver trazabilidad');
});
