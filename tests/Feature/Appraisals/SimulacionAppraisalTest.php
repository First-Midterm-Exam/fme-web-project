<?php

use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\AppraisalSimulation;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\ReadinessMeasurement;
use App\Models\User;
use App\Services\AppraisalSimulationService;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;

beforeEach(function (): void {
    $this->seed(CmmiCatalogSeeder::class);
    $this->seed(EvidenceStatusSeeder::class);
});

/**
 * @return array{0: Appraisal, 1: Project, 2: User}
 */
function escenarioSimulacion(): array
{
    $jefe = User::factory()->jefeProyecto()->create(['name' => 'Laura Jefa']);

    $proyecto = Project::create(['name' => 'Proyecto Simulado', 'code' => 'P-SIM', 'start_date' => '2026-01-01', 'status' => 'activo']);
    $proyecto->users()->attach($jefe->id);

    $appraisal = Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => 'Appraisal Simulado',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $practicas = Practice::whereIn('code', ['PLAN 1.1', 'EST 1.1'])->get();
    $appraisal->practices()->sync(AppraisalScope::pivotFor($practicas->pluck('id')));

    return [$appraisal, $proyecto, $jefe];
}

function practicaConforme(Appraisal $appraisal, Project $proyecto, User $usuario, string $codigo, string $codigoEvidencia): PracticeEvaluation
{
    $practica = Practice::where('code', $codigo)->firstOrFail();

    $evaluacion = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $practica->id,
        'status' => PracticeEvaluation::STATUS_CUMPLE,
    ]);

    foreach ($practica->criteria as $criterio) {
        CriterionCheck::create([
            'practice_evaluation_id' => $evaluacion->id,
            'practice_criterion_id' => $criterio->id,
            'status' => CriterionCheck::STATUS_CUMPLE,
        ]);
    }

    $evidencia = Evidence::create([
        'code' => $codigoEvidencia,
        'project_id' => $proyecto->id,
        'name' => 'Evidencia verificada de '.$codigo,
        'type' => Evidence::TYPE_ACTA,
        'status_id' => EvidenceStatus::VERIFICADA,
        'uploaded_by' => $usuario->id,
    ]);
    $evidencia->practices()->attach($practica->id);

    return $evaluacion;
}

test('el veredicto es listo cuando todas las practicas del nivel cumplen y no hay gaps abiertos', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    practicaConforme($appraisal, $proyecto, $jefe, 'EST 1.1', 'EV-0002');

    $resultado = app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, 1);

    expect($resultado->score)->toBe(100.0)
        ->and($resultado->readiness)->toBe(ReadinessMeasurement::STATUS_LISTO)
        ->and($resultado->brechas)->toBe([])
        ->and($resultado->desglose['practicas_evaluadas'])->toBe(2);
});

test('el veredicto es listo con condiciones cuando solo quedan gaps no bloqueantes', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    $evaluacion = practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    practicaConforme($appraisal, $proyecto, $jefe, 'EST 1.1', 'EV-0002');

    Gap::create([
        'code' => 'GAP-0001',
        'practice_evaluation_id' => $evaluacion->id,
        'title' => 'Actualizar el cronograma',
        'status' => Gap::STATUS_ABIERTO,
        'severity' => Gap::SEVERITY_MEDIA,
    ]);

    $resultado = app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, 1);

    expect($resultado->score)->toBe(100.0)
        ->and($resultado->readiness)->toBe(ReadinessMeasurement::STATUS_LISTO_CON_CONDICIONES)
        ->and($resultado->brechas)->toHaveCount(1)
        ->and($resultado->brechas[0]['tipo'])->toBe(AppraisalSimulationService::TIPO_GAP)
        ->and($resultado->brechas[0]['bloqueante'])->toBeFalse();
});

test('un gap critico abierto deja el veredicto en no listo', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    $evaluacion = practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    practicaConforme($appraisal, $proyecto, $jefe, 'EST 1.1', 'EV-0002');

    Gap::create([
        'code' => 'GAP-0002',
        'practice_evaluation_id' => $evaluacion->id,
        'title' => 'No existe el plan aprobado',
        'status' => Gap::STATUS_ABIERTO,
        'severity' => Gap::SEVERITY_CRITICA,
    ]);

    $resultado = app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, 1);

    expect($resultado->score)->toBe(50.0)
        ->and($resultado->readiness)->toBe(ReadinessMeasurement::STATUS_NO_LISTO)
        ->and($resultado->desglose['gaps_criticos'])->toBe(1);
});

test('las brechas identifican criterios pendientes, practicas sin evaluar y falta de evidencia verificada', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $evaluacion = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $practica->id,
        'status' => PracticeEvaluation::STATUS_PARCIAL,
    ]);

    CriterionCheck::create([
        'practice_evaluation_id' => $evaluacion->id,
        'practice_criterion_id' => $practica->criteria->first()->id,
        'status' => CriterionCheck::STATUS_NO_CUMPLE,
    ]);

    $resultado = app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, 1);

    $tipos = array_column($resultado->brechas, 'tipo');

    expect($resultado->score)->toBe(0.0)
        ->and($resultado->readiness)->toBe(ReadinessMeasurement::STATUS_NO_LISTO)
        ->and($tipos)->toContain(AppraisalSimulationService::TIPO_CRITERIO)
        ->and($tipos)->toContain(AppraisalSimulationService::TIPO_EVIDENCIA)
        ->and($tipos)->toContain(AppraisalSimulationService::TIPO_EVALUACION);
});

test('la simulacion solo considera las practicas hasta el nivel objetivo', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    practicaConforme($appraisal, $proyecto, $jefe, 'EST 1.1', 'EV-0002');

    $nivelSuperior = Practice::where('level', '>', 1)->firstOrFail();
    $appraisal->practices()->attach($nivelSuperior->id, ['practice_area_id' => $nivelSuperior->practice_area_id]);

    $servicio = app(AppraisalSimulationService::class);

    expect($servicio->ejecutarSimulacion($appraisal->id, 1)->desglose['practicas_evaluadas'])->toBe(2)
        ->and($servicio->ejecutarSimulacion($appraisal->id, $nivelSuperior->level)->desglose['practicas_evaluadas'])->toBe(3);
});

test('la simulacion persiste la ejecucion y su medicion de readiness', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    practicaConforme($appraisal, $proyecto, $jefe, 'EST 1.1', 'EV-0002');
    $gestor = User::factory()->gestorProcesos()->create();

    $resultado = app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, 1, $gestor);

    $this->assertDatabaseHas('appraisal_simulations', [
        'id' => $resultado->simulacionId,
        'appraisal_id' => $appraisal->id,
        'executed_by' => $gestor->id,
        'target_level' => 1,
        'passed_practices' => 2,
    ]);

    $this->assertDatabaseHas('readiness_measurements', [
        'appraisal_simulation_id' => $resultado->simulacionId,
        'appraisal_id' => $appraisal->id,
        'status' => ReadinessMeasurement::STATUS_LISTO,
        'level' => 1,
    ]);
});

test('un gestor de procesos ejecuta la simulacion y recibe el resultado en json', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    practicaConforme($appraisal, $proyecto, $jefe, 'EST 1.1', 'EV-0002');

    $respuesta = $this->actingAs(User::factory()->gestorProcesos()->create())
        ->postJson(route('appraisals.simulaciones.store', $appraisal), ['nivelObjetivo' => 1]);

    $respuesta->assertOk()
        ->assertJsonPath('resultadoSimulacion.score', 100)
        ->assertJsonPath('resultadoSimulacion.nivel', 1)
        ->assertJsonPath('resultadoSimulacion.readiness', ReadinessMeasurement::STATUS_LISTO)
        ->assertJsonStructure(['resultadoSimulacion' => ['simulacion_id', 'score', 'nivel', 'readiness', 'brechas', 'desglose', 'generado_en']]);
});

test('el nivel objetivo es obligatorio y debe estar entre 1 y 5', function (mixed $nivel): void {
    [$appraisal] = escenarioSimulacion();

    $this->actingAs(User::factory()->gestorProcesos()->create())
        ->postJson(route('appraisals.simulaciones.store', $appraisal), ['nivelObjetivo' => $nivel])
        ->assertStatus(422)
        ->assertJsonValidationErrors('nivelObjetivo');
})->with([null, 0, 6, 'nivel dos']);

test('un jefe de proyecto consulta la simulacion de su proyecto pero no puede ejecutarla', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');

    $this->actingAs($jefe)
        ->get(route('appraisals.simulaciones.index', $appraisal))
        ->assertOk()
        ->assertSee('Simulación de Appraisal');

    $this->actingAs($jefe)
        ->postJson(route('appraisals.simulaciones.store', $appraisal), ['nivelObjetivo' => 1])
        ->assertForbidden();

    expect(AppraisalSimulation::count())->toBe(0);
});

test('la simulacion queda restringida a los usuarios sin acceso al appraisal', function (): void {
    [$appraisal] = escenarioSimulacion();

    $this->get(route('appraisals.simulaciones.index', $appraisal))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->colaborador()->create())
        ->get(route('appraisals.simulaciones.index', $appraisal))
        ->assertForbidden();

    $this->actingAs(User::factory()->administrador()->create())
        ->postJson(route('appraisals.simulaciones.store', $appraisal), ['nivelObjetivo' => 1])
        ->assertForbidden();

    $this->actingAs(User::factory()->jefeProyecto()->create())
        ->get(route('appraisals.simulaciones.index', $appraisal))
        ->assertForbidden();
});

test('el historial muestra las simulaciones previas del appraisal', function (): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();
    practicaConforme($appraisal, $proyecto, $jefe, 'PLAN 1.1', 'EV-0001');
    $gestor = User::factory()->gestorProcesos()->create(['name' => 'Mario Gestor']);

    app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, 1, $gestor);

    $this->actingAs($gestor)
        ->get(route('appraisals.simulaciones.index', $appraisal))
        ->assertOk()
        ->assertSee('Historial de simulaciones')
        ->assertSee('Mario Gestor')
        ->assertSee(ReadinessMeasurement::STATUS_NO_LISTO);
});

test('los accesos del menu llevan al appraisal visible por el usuario', function (string $ruta, string $destino): void {
    [$appraisal, $proyecto, $jefe] = escenarioSimulacion();

    $this->actingAs($jefe)
        ->get(route($ruta))
        ->assertRedirect(route($destino, $appraisal));
})->with([
    ['readiness.index', 'appraisals.readiness'],
    ['readiness.simulacion', 'appraisals.simulaciones.index'],
]);
