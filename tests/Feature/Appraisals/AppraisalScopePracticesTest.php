<?php

use App\Livewire\Appraisals\AppraisalPracticeList;
use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeAssessment;
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
 * Helper to set up an active project and appraisal with in-scope practices.
 *
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
        PracticeAssessment::firstOrCreate([
            'appraisal_id' => $appraisal->id,
            'practice_id' => $practice->id,
        ], [
            'status' => PracticeAssessment::STATUS_NO_EVALUADA,
        ]);
    }

    return [$gestor, $project, $appraisal];
}

// =============================================================================
// 1 & 2. Muestra únicamente prácticas del alcance y excluye prácticas fuera de alcance
// =============================================================================
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

// =============================================================================
// 3. Se agrupan correctamente por Practice Area
// =============================================================================
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

// =============================================================================
// 4. Funciona el filtro por Practice Area
// =============================================================================
test('filter by practice area reduces the list correctly', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'EST 1.1']);
    $planArea = PracticeArea::where('code', 'PLAN')->firstOrFail();
    $estArea = PracticeArea::where('code', 'EST')->firstOrFail();

    $this->actingAs($gestor);

    // Filter by Planning
    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('areaFilter', $planArea->id)
        ->assertSee('PLAN 1.1')
        ->assertDontSee('EST 1.1');

    // Filter by Estimating
    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('areaFilter', $estArea->id)
        ->assertSee('EST 1.1')
        ->assertDontSee('PLAN 1.1');
});

// =============================================================================
// 5. Funciona el filtro por estado de evaluación
// =============================================================================
test('filter by assessment status filters practices correctly', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'PLAN 2.1']);

    $plan11 = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $plan21 = Practice::where('code', 'PLAN 2.1')->firstOrFail();

    // Set one to 'Cumple' and the other remains 'No evaluada'
    PracticeAssessment::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $plan11->id)
        ->update(['status' => PracticeAssessment::STATUS_CUMPLE]);

    $this->actingAs($gestor);

    // Filter by 'Cumple'
    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('statusFilter', PracticeAssessment::STATUS_CUMPLE)
        ->assertSee('PLAN 1.1')
        ->assertDontSee('PLAN 2.1');

    // Filter by 'No evaluada'
    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->set('statusFilter', PracticeAssessment::STATUS_NO_EVALUADA)
        ->assertSee('PLAN 2.1')
        ->assertDontSee('PLAN 1.1');
});

// =============================================================================
// 6. El detalle muestra los criterios de HU-07
// =============================================================================
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

// =============================================================================
// 7. Una práctica No evaluada muestra 0%
// =============================================================================
test('a practice in No evaluada status displays 0 percent completion', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertSee('0%');
});

// =============================================================================
// 8. Se muestran correctamente los estados provenientes de practice_assessments
// =============================================================================
test('displays assessment status read from practice_assessments', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1', 'PLAN 2.1', 'EST 1.1']);

    $plan11 = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $plan21 = Practice::where('code', 'PLAN 2.1')->firstOrFail();
    $est11 = Practice::where('code', 'EST 1.1')->firstOrFail();

    PracticeAssessment::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $plan11->id)
        ->update(['status' => PracticeAssessment::STATUS_VERIFICADA]);

    PracticeAssessment::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $plan21->id)
        ->update(['status' => PracticeAssessment::STATUS_PARCIAL]);

    PracticeAssessment::where('appraisal_id', $appraisal->id)
        ->where('practice_id', $est11->id)
        ->update(['status' => PracticeAssessment::STATUS_NO_CUMPLE]);

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertSee('Verificada')
        ->assertSee('Parcial')
        ->assertSee('No cumple');
});

// =============================================================================
// 9. Aparece el estado vacío de observaciones cuando no existen
// =============================================================================
test('detail modal displays empty state for observations when none exist', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);
    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->call('openDetailModal', $practice->id)
        ->assertSee('Sin observaciones registradas');
});

// =============================================================================
// 10. Aparece el estado vacío de evidencias cuando no existen
// =============================================================================
test('detail modal displays empty state for evidences when none exist', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);
    $practice = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $this->actingAs($gestor);

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->call('openDetailModal', $practice->id)
        ->assertSee('No existen evidencias asociadas');
});

// =============================================================================
// 11. Un usuario no asignado recibe 403
// =============================================================================
test('an unassigned user receives 403 when trying to access appraisal practices', function () {
    [$gestor, $project, $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);

    $unassignedPm = User::factory()->jefeProyecto()->create();
    $unassignedColaborador = User::factory()->colaborador()->create();

    // Unassigned PM
    $this->actingAs($unassignedPm);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertForbidden();

    // Unassigned Colaborador
    $this->actingAs($unassignedColaborador);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertForbidden();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertForbidden();

    // Assigned PM can access
    $assignedPm = User::factory()->jefeProyecto()->create();
    $project->users()->attach($assignedPm->id);

    $this->actingAs($assignedPm);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertOk();

    Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertOk();

    // Gestor de procesos can access
    $this->actingAs($gestor);
    $this->get(route('appraisals.practices', $appraisal->id))
        ->assertOk();
});

// =============================================================================
// 12. La pantalla es estrictamente de consulta y no ofrece acciones de mutación
// =============================================================================
test('the component is strictly read-only and does not offer mutation actions for states or criteria', function () {
    [$gestor, , $appraisal] = setupAppraisalWithScope(['PLAN 1.1']);

    $this->actingAs($gestor);

    $component = Livewire::test(AppraisalPracticeList::class, ['appraisal' => $appraisal])
        ->assertOk()
        ->assertDontSee('Guardar')
        ->assertDontSee('btn-save')
        ->assertDontSee('Eliminar');

    // Verify component does not have mutation methods
    expect(method_exists($component->instance(), 'save'))->toBeFalse()
        ->and(method_exists($component->instance(), 'updateStatus'))->toBeFalse()
        ->and(method_exists($component->instance(), 'updateCriteria'))->toBeFalse();
});
