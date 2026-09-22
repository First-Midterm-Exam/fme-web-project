<?php

use App\Livewire\Dashboard\Statistics;
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
use App\Services\StatisticsService;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(CmmiCatalogSeeder::class);
    $this->seed(EvidenceStatusSeeder::class);
});

/**
 * @return array{0: Appraisal, 1: Project, 2: User}
 */
function escenarioEstadisticas(string $nombre = 'Appraisal Estadísticas', string $codigoProyecto = 'P-EST'): array
{
    $jefe = User::factory()->jefeProyecto()->create();

    $proyecto = Project::create(['name' => 'Proyecto '.$codigoProyecto, 'code' => $codigoProyecto, 'start_date' => '2026-01-01', 'status' => 'activo']);
    $proyecto->users()->attach($jefe->id);

    $appraisal = Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => $nombre,
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $practicas = Practice::whereIn('code', ['PLAN 1.1', 'PLAN 2.1', 'EST 1.1'])->get()->keyBy('code');
    $appraisal->practices()->sync(AppraisalScope::pivotFor($practicas->pluck('id')));

    $evaluaciones = [];
    foreach (['PLAN 1.1' => PracticeEvaluation::STATUS_CUMPLE, 'PLAN 2.1' => PracticeEvaluation::STATUS_PARCIAL, 'EST 1.1' => PracticeEvaluation::STATUS_NO_EVALUADA] as $codigo => $estado) {
        $evaluaciones[$codigo] = PracticeEvaluation::create([
            'appraisal_id' => $appraisal->id,
            'practice_id' => $practicas[$codigo]->id,
            'status' => $estado,
        ]);
    }

    foreach ($practicas['PLAN 1.1']->criteria as $criterio) {
        CriterionCheck::create(['practice_evaluation_id' => $evaluaciones['PLAN 1.1']->id, 'practice_criterion_id' => $criterio->id, 'status' => CriterionCheck::STATUS_CUMPLE]);
    }

    CriterionCheck::create([
        'practice_evaluation_id' => $evaluaciones['PLAN 2.1']->id,
        'practice_criterion_id' => $practicas['PLAN 2.1']->criteria->first()->id,
        'status' => CriterionCheck::STATUS_CUMPLE,
    ]);

    foreach ([EvidenceStatus::REGISTRADA, EvidenceStatus::REGISTRADA, EvidenceStatus::VERIFICADA, EvidenceStatus::RECHAZADA] as $indice => $estado) {
        Evidence::create([
            'code' => $codigoProyecto.'-EV-'.$indice,
            'project_id' => $proyecto->id,
            'name' => 'Evidencia '.$indice,
            'type' => Evidence::TYPE_PLAN,
            'status_id' => $estado,
            'uploaded_by' => $jefe->id,
        ]);
    }

    $gapAbierto = Gap::create([
        'code' => $codigoProyecto.'-GAP-1',
        'practice_evaluation_id' => $evaluaciones['PLAN 2.1']->id,
        'title' => 'Gap vencido',
        'status' => Gap::STATUS_EN_PROGRESO,
        'severity' => Gap::SEVERITY_CRITICA,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    Gap::create([
        'code' => $codigoProyecto.'-GAP-2',
        'practice_evaluation_id' => $evaluaciones['PLAN 2.1']->id,
        'title' => 'Gap abierto',
        'status' => Gap::STATUS_ABIERTO,
        'severity' => Gap::SEVERITY_MEDIA,
    ]);

    $gapCerrado = Gap::create([
        'code' => $codigoProyecto.'-GAP-3',
        'practice_evaluation_id' => $evaluaciones['PLAN 1.1']->id,
        'title' => 'Gap cerrado',
        'status' => Gap::STATUS_CERRADO,
        'severity' => Gap::SEVERITY_ALTA,
    ]);

    CorrectiveAction::create([
        'gap_id' => $gapAbierto->id,
        'description' => 'Acción en curso',
        'responsible_id' => $jefe->id,
        'due_date' => now()->subDay()->toDateString(),
        'progress_percent' => 40,
        'status' => CorrectiveAction::STATUS_EN_PROGRESO,
    ]);

    CorrectiveAction::create([
        'gap_id' => $gapCerrado->id,
        'description' => 'Acción terminada',
        'responsible_id' => $jefe->id,
        'due_date' => now()->addDays(10)->toDateString(),
        'progress_percent' => 100,
        'status' => CorrectiveAction::STATUS_CERRADA,
    ]);

    return [$appraisal, $proyecto, $jefe];
}

test('the statistics summarize practices, evidences, gaps and actions of the appraisal', function () {
    [$appraisal] = escenarioEstadisticas();

    $stats = app(StatisticsService::class)->forAppraisal($appraisal);

    expect($stats['practices']['total'])->toBe(3)
        ->and($stats['practices']['average_compliance'])->toBe(50)
        ->and($stats['practices']['by_status'])->toBe([
            'No evaluada' => 1,
            'No cumple' => 0,
            'Parcial' => 1,
            'Cumple' => 1,
            'Verificada' => 0,
        ])
        ->and(collect($stats['practices']['by_area'])->pluck('compliance', 'code')->all())->toBe(['EST' => 0, 'PLAN' => 75])
        ->and($stats['evidences']['total'])->toBe(4)
        ->and($stats['evidences']['pending'])->toBe(2)
        ->and($stats['evidences']['by_status'])->toBe(['Registrada' => 2, 'Verificado' => 1, 'Observado' => 0, 'Rechazado' => 1])
        ->and($stats['gaps']['total'])->toBe(3)
        ->and($stats['gaps']['open'])->toBe(2)
        ->and($stats['gaps']['closed'])->toBe(1)
        ->and($stats['gaps']['overdue'])->toBe(1)
        ->and($stats['gaps']['by_status'])->toBe(['Abierto' => 1, 'En progreso' => 1, 'Resuelto' => 0, 'Verificado' => 0, 'Cerrado' => 1])
        ->and($stats['gaps']['by_severity'])->toBe(['Baja' => 0, 'Media' => 1, 'Alta' => 1, 'Crítica' => 1])
        ->and($stats['actions']['total'])->toBe(2)
        ->and($stats['actions']['active'])->toBe(1)
        ->and($stats['actions']['average_progress'])->toBe(70)
        ->and($stats['actions']['overdue'])->toBe(1)
        ->and($stats['actions']['by_status'])->toBe(['Abierta' => 0, 'En progreso' => 1, 'Cerrada' => 1]);
});

test('the statistics only count data of the selected appraisal', function () {
    [$primero] = escenarioEstadisticas('Appraisal Uno', 'P-UNO');
    escenarioEstadisticas('Appraisal Dos', 'P-DOS');

    $stats = app(StatisticsService::class)->forAppraisal($primero);

    expect($stats['evidences']['total'])->toBe(4)
        ->and($stats['gaps']['total'])->toBe(3)
        ->and($stats['actions']['total'])->toBe(2);
});

test('the gestor sees the statistics with charts on the main panel', function () {
    [$appraisal] = escenarioEstadisticas();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Estado general del appraisal')
        ->assertSee('Prácticas por estado')
        ->assertSee('Gaps por severidad')
        ->assertSee('<canvas', false)
        ->assertSee('chart.umd.min.js', false)
        ->assertSee(route('evidencias.verificacion'), false)
        ->assertSee(route('appraisals.gaps', $appraisal->id), false);
});

test('an assigned jefe de proyecto sees the statistics of their appraisal', function () {
    [$appraisal, , $jefe] = escenarioEstadisticas();

    $this->actingAs($jefe);

    Livewire::test(Statistics::class)
        ->assertSet('appraisalId', $appraisal->id)
        ->assertSee('Appraisal Estadísticas')
        ->assertDontSee('Ir a verificación');
});

test('roles outside the use case do not see the statistics on the main panel', function (string $estado) {
    escenarioEstadisticas();

    $this->actingAs(User::factory()->{$estado}()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Estado general del appraisal');
})->with(['administrador', 'colaborador']);

test('a jefe without assigned appraisals sees an empty state', function () {
    escenarioEstadisticas();

    $this->actingAs(User::factory()->jefeProyecto()->create());

    Livewire::test(Statistics::class)
        ->assertSet('appraisalId', null)
        ->assertSee('No tienes appraisals disponibles para mostrar estadísticas.');
});

test('changing the appraisal refreshes the statistics', function () {
    [$primero] = escenarioEstadisticas('Appraisal Uno', 'P-UNO');
    [$segundo] = escenarioEstadisticas('Appraisal Dos', 'P-DOS');
    Gap::where('code', 'P-DOS-GAP-2')->delete();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(Statistics::class)
        ->set('appraisalId', $primero->id)
        ->assertViewHas('stats', fn (array $stats): bool => $stats['gaps']['total'] === 3)
        ->set('appraisalId', $segundo->id)
        ->assertViewHas('stats', fn (array $stats): bool => $stats['gaps']['total'] === 2);
});

test('a jefe cannot open the statistics of an appraisal outside their projects', function () {
    [, , $jefe] = escenarioEstadisticas('Appraisal Propio', 'P-PRO');
    [$ajeno] = escenarioEstadisticas('Appraisal Ajeno', 'P-AJE');

    $this->actingAs($jefe);

    Livewire::test(Statistics::class)
        ->set('appraisalId', $ajeno->id)
        ->assertForbidden();
});

test('charts without data show an empty message', function () {
    $proyecto = Project::create(['name' => 'Vacío', 'code' => 'P-VAC', 'start_date' => '2026-01-01', 'status' => 'activo']);
    Appraisal::create(['project_id' => $proyecto->id, 'name' => 'Appraisal Vacío', 'domain' => 'Development', 'target_level' => 2, 'target_date' => '2026-12-31', 'status' => 'activo']);

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(Statistics::class)
        ->assertSee('Sin datos registrados.')
        ->assertDontSee('<canvas', false);
});
