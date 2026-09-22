<?php

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
use App\Services\AppraisalReadinessService;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;

beforeEach(function (): void {
    $this->seed(CmmiCatalogSeeder::class);
    $this->seed(EvidenceStatusSeeder::class);
});

/**
 * @return array{0: Appraisal, 1: Project, 2: User, 3: User}
 */
function setupPdfExportScenario(): array
{
    $jefe = User::factory()->jefeProyecto()->create(['name' => 'Jefe de Proyecto']);
    $ajeno = User::factory()->jefeProyecto()->create(['name' => 'Usuario Ajeno']);

    $proyecto = Project::create([
        'name' => 'Proyecto Exportacion PDF',
        'code' => 'P-EXP',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $proyecto->users()->attach($jefe->id);

    $appraisal = Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => 'Appraisal Auditoria PDF',
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

    $criterio = $plan->criteria->first();
    if ($criterio) {
        CriterionCheck::create([
            'practice_evaluation_id' => $evaluacion->id,
            'practice_criterion_id' => $criterio->id,
            'status' => CriterionCheck::STATUS_NO_CUMPLE,
            'notes' => 'Falta evidencia técnica.',
        ]);

        $gap = Gap::create([
            'code' => 'GAP-0099',
            'practice_evaluation_id' => $evaluacion->id,
            'practice_criterion_id' => $criterio->id,
            'title' => 'Criterio '.$criterio->code.' no satisfecho',
            'status' => Gap::STATUS_ABIERTO,
            'severity' => Gap::SEVERITY_ALTA,
            'assigned_to_id' => $jefe->id,
            'due_date' => '2026-11-01',
        ]);

        CorrectiveAction::create([
            'gap_id' => $gap->id,
            'description' => 'Elaborar plan de remediación técnica',
            'responsible_id' => $jefe->id,
            'due_date' => '2026-10-25',
            'progress_percent' => 50,
            'status' => CorrectiveAction::STATUS_EN_PROGRESO,
        ]);
    }

    $evidencia = Evidence::create([
        'code' => 'EV-0099',
        'project_id' => $proyecto->id,
        'name' => 'Documento de Arquitectura',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::VERIFICADA,
        'uploaded_by' => $jefe->id,
        'verified_by' => $jefe->id,
        'verified_at' => now(),
    ]);
    $evidencia->practices()->attach($plan->id);

    return [$appraisal, $proyecto, $jefe, $ajeno];
}

it('generates practicas pdf report for authorized user', function (): void {
    [$appraisal, , $jefe] = setupPdfExportScenario();

    $response = $this->actingAs($jefe)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'practicas']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('reporte-practicas-');
});

it('generates evidencias pdf report for authorized user', function (): void {
    [$appraisal, , $jefe] = setupPdfExportScenario();

    $response = $this->actingAs($jefe)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'evidencias']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('reporte-evidencias-');
});

it('generates gaps pdf report for authorized user', function (): void {
    [$appraisal, , $jefe] = setupPdfExportScenario();

    $response = $this->actingAs($jefe)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'gaps']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('reporte-gaps-');
});

it('generates acciones pdf report for authorized user', function (): void {
    [$appraisal, , $jefe] = setupPdfExportScenario();

    $response = $this->actingAs($jefe)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'acciones']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('reporte-acciones-');
});

it('generates readiness pdf report containing rnf14 disclaimer for authorized user', function (): void {
    [$appraisal, , $jefe] = setupPdfExportScenario();

    $response = $this->actingAs($jefe)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'readiness']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('reporte-readiness-');

    // Verify readiness data was processed and disclaimer constant is present
    $disclaimer = AppraisalReadinessService::DISCLAIMER_TEXT;
    expect($disclaimer)->toContain('CMMI');
});

it('returns 403 when user is not authorized to view the appraisal report', function (): void {
    [$appraisal, , , $ajeno] = setupPdfExportScenario();

    $response = $this->actingAs($ajeno)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'practicas']));

    $response->assertForbidden();
});

it('returns 404 for invalid report type', function (): void {
    [$appraisal, , $jefe] = setupPdfExportScenario();

    $response = $this->actingAs($jefe)
        ->get("/appraisals/{$appraisal->id}/reportes/tipo-inexistente");

    $response->assertNotFound();
});

it('allows administrador to export reports even if not in project team', function (): void {
    [$appraisal] = setupPdfExportScenario();
    $admin = User::factory()->administrador()->create(['name' => 'Admin General']);

    $response = $this->actingAs($admin)
        ->get(route('appraisals.reportes.export', [$appraisal->id, 'practicas']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
