<?php

use App\Actions\Evidences\VerifyEvidenceAction;
use App\Livewire\Appraisals\PracticeChecklist;
use App\Livewire\Evidences\PendingEvidenceVerification;
use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use App\Services\Asistente\Fuentes\GapsRegistrados;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(CmmiCatalogSeeder::class);
    $this->seed(EvidenceStatusSeeder::class);
});

/**
 * @param  list<string>  $codigos
 * @return array{0: Appraisal, 1: Project}
 */
function appraisalParaGaps(array $codigos = ['PLAN 1.1', 'EST 1.1'], string $estado = Appraisal::STATUS_ACTIVO): array
{
    $proyecto = Project::create([
        'name' => 'Proyecto Gaps',
        'code' => 'P-GAP-'.rand(100, 999),
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => 'Appraisal Gaps',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-12-31',
        'status' => $estado,
    ]);

    $practicas = Practice::whereIn('code', $codigos)->get();
    $appraisal->practices()->sync(AppraisalScope::pivotFor($practicas->pluck('id')));

    foreach ($practicas as $practica) {
        PracticeEvaluation::create([
            'appraisal_id' => $appraisal->id,
            'practice_id' => $practica->id,
            'status' => PracticeEvaluation::STATUS_NO_EVALUADA,
        ]);
    }

    return [$appraisal, $proyecto];
}

/**
 * @param  list<string>  $codigosPractica
 */
function evidenciaRegistradaPara(Project $proyecto, array $codigosPractica, User $autor): Evidence
{
    $evidencia = Evidence::create([
        'code' => Evidence::generateNextCode(),
        'project_id' => $proyecto->id,
        'name' => 'Plan de proyecto',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $autor->id,
    ]);

    $evidencia->practices()->sync(Practice::whereIn('code', $codigosPractica)->pluck('id'));

    return $evidencia;
}

function evaluacionDe(Appraisal $appraisal, string $codigo): PracticeEvaluation
{
    return PracticeEvaluation::where('appraisal_id', $appraisal->id)
        ->whereRelation('practice', 'code', $codigo)
        ->firstOrFail();
}

test('marking a criterion as not met generates an open gap', function () {
    [$appraisal] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $criterio = $practica->criteria()->firstOrFail();
    $gestor = User::factory()->gestorProcesos()->create();

    $this->actingAs($gestor);

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_NO_CUMPLE)
        ->assertSee('Gap GAP-0001 abierto.');

    $gap = Gap::firstOrFail();

    expect($gap->code)->toBe('GAP-0001')
        ->and($gap->practice_evaluation_id)->toBe(evaluacionDe($appraisal, 'PLAN 1.1')->id)
        ->and($gap->practice_criterion_id)->toBe($criterio->id)
        ->and($gap->evidence_id)->toBeNull()
        ->and($gap->title)->toBe('Criterio '.$criterio->code.' no cumplido')
        ->and($gap->description)->toBe($criterio->description)
        ->and($gap->status)->toBe(Gap::STATUS_ABIERTO)
        ->and($gap->generated_by)->toBe($gestor->id);
});

test('marking the same criterion as not met again does not duplicate the gap', function () {
    [$appraisal] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $criterio = $practica->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_NO_CUMPLE)
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_CUMPLE)
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_NO_CUMPLE);

    expect(Gap::count())->toBe(1);
});

test('marking the criterion as met afterwards keeps the gap open', function () {
    [$appraisal] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $criterio = $practica->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_NO_CUMPLE)
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_CUMPLE);

    expect(Gap::firstOrFail()->status)->toBe(Gap::STATUS_ABIERTO);
});

test('other criterion marks do not generate gaps', function (string $estado) {
    [$appraisal] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markCriterion', $practica->criteria()->firstOrFail()->id, $estado);

    expect(Gap::count())->toBe(0);
})->with([CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_NO_APLICA, CriterionCheck::STATUS_PENDIENTE]);

test('marking every criterion as not met generates one gap per active criterion', function () {
    [$appraisal] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    [$primero, $segundo] = $practica->criteria->values()->all();
    $segundo->update(['estado' => false]);

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markAll', CriterionCheck::STATUS_NO_CUMPLE)
        ->assertSee('Gaps abiertos: GAP-0001.');

    expect(Gap::pluck('practice_criterion_id')->all())->toBe([$primero->id]);
});

test('the checklist shows the open gap next to the criterion', function () {
    [$appraisal] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $criterio = $practica->criteria()->firstOrFail();

    $this->actingAs(User::factory()->gestorProcesos()->create());

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markCriterion', $criterio->id, CriterionCheck::STATUS_NO_CUMPLE);

    $this->get(route('appraisals.practices.checklist', [$appraisal->id, $practica->id]))
        ->assertOk()
        ->assertSee('GAP-0001 · Abierto');
});

test('rejecting an evidence generates one gap per associated practice of the active appraisal', function () {
    [$appraisal, $proyecto] = appraisalParaGaps(['PLAN 1.1', 'EST 1.1', 'PLAN 2.1']);
    $admin = User::factory()->administrador()->create();
    $evidencia = evidenciaRegistradaPara($proyecto, ['PLAN 1.1', 'EST 1.1'], $admin);

    app(VerifyEvidenceAction::class)->execute($evidencia, $admin, EvidenceStatus::RECHAZADA, 'El documento no tiene firmas.');

    $gaps = Gap::orderBy('code')->get();

    expect($gaps)->toHaveCount(2)
        ->and($gaps->pluck('practice_evaluation_id')->all())->toEqualCanonicalizing([
            evaluacionDe($appraisal, 'PLAN 1.1')->id,
            evaluacionDe($appraisal, 'EST 1.1')->id,
        ])
        ->and($gaps->pluck('evidence_id')->unique()->all())->toBe([$evidencia->id])
        ->and($gaps->first()->title)->toBe('Evidencia '.$evidencia->code.' rechazada')
        ->and($gaps->first()->description)->toBe('El documento no tiene firmas.')
        ->and($gaps->first()->generated_by)->toBe($admin->id);
});

test('verifying or observing an evidence does not generate gaps', function (int $estado) {
    [, $proyecto] = appraisalParaGaps();
    $admin = User::factory()->administrador()->create();
    $evidencia = evidenciaRegistradaPara($proyecto, ['PLAN 1.1'], $admin);

    app(VerifyEvidenceAction::class)->execute($evidencia, $admin, $estado, 'Motivo de la decisión.');

    expect(Gap::count())->toBe(0);
})->with([EvidenceStatus::VERIFICADA, EvidenceStatus::OBSERVADA]);

test('a rejected evidence without practices of an active appraisal does not generate gaps', function (string $estadoAppraisal, array $practicas) {
    [, $proyecto] = appraisalParaGaps(['PLAN 1.1'], $estadoAppraisal);
    $admin = User::factory()->administrador()->create();
    $evidencia = evidenciaRegistradaPara($proyecto, $practicas, $admin);

    app(VerifyEvidenceAction::class)->execute($evidencia, $admin, EvidenceStatus::RECHAZADA, 'Documento ilegible.');

    expect(Gap::count())->toBe(0);
})->with([
    'appraisal cerrado' => [Appraisal::STATUS_CERRADO, ['PLAN 1.1']],
    'sin prácticas asociadas' => [Appraisal::STATUS_ACTIVO, []],
    'práctica fuera del alcance' => [Appraisal::STATUS_ACTIVO, ['PLAN 2.1']],
]);

test('the verification screen tells which gaps were generated', function () {
    [, $proyecto] = appraisalParaGaps();
    $admin = User::factory()->administrador()->create();
    $evidencia = evidenciaRegistradaPara($proyecto, ['PLAN 1.1', 'EST 1.1'], $admin);

    $this->actingAs($admin);

    Livewire::test(PendingEvidenceVerification::class)
        ->call('openVerifyModal', $evidencia->id)
        ->set('verificationStatus', EvidenceStatus::RECHAZADA)
        ->set('verificationReason', 'Falta la aprobación del patrocinador.')
        ->call('submitVerification')
        ->assertHasNoErrors()
        ->assertSee('Se generaron los gaps: GAP-0001, GAP-0002.');
});

test('gap codes are sequential across criteria and evidences', function () {
    [$appraisal, $proyecto] = appraisalParaGaps();
    $practica = Practice::where('code', 'PLAN 1.1')->firstOrFail();
    $gestor = User::factory()->gestorProcesos()->create();
    $admin = User::factory()->administrador()->create();

    $this->actingAs($gestor);

    Livewire::test(PracticeChecklist::class, ['appraisal' => $appraisal, 'practice' => $practica])
        ->call('markCriterion', $practica->criteria()->firstOrFail()->id, CriterionCheck::STATUS_NO_CUMPLE);

    app(VerifyEvidenceAction::class)->execute(
        evidenciaRegistradaPara($proyecto, ['EST 1.1'], $admin),
        $admin,
        EvidenceStatus::RECHAZADA,
        'Evidencia incompleta.'
    );

    expect(Gap::orderBy('id')->pluck('code')->all())->toBe(['GAP-0001', 'GAP-0002']);
});

test('the mobile assistant source returns only open gaps of the appraisal', function () {
    [$appraisal] = appraisalParaGaps();
    [$otroAppraisal] = appraisalParaGaps();
    $gestor = User::factory()->gestorProcesos()->create();

    $crear = fn (Appraisal $destino, string $codigo, string $estado) => Gap::create([
        'code' => $codigo,
        'practice_evaluation_id' => evaluacionDe($destino, 'PLAN 1.1')->id,
        'title' => 'Gap '.$codigo,
        'status' => $estado,
        'generated_by' => $gestor->id,
    ]);

    $crear($appraisal, 'GAP-0001', Gap::STATUS_ABIERTO);
    $crear($appraisal, 'GAP-0002', Gap::STATUS_CERRADO);
    $crear($otroAppraisal, 'GAP-0003', Gap::STATUS_ABIERTO);

    $gaps = (new GapsRegistrados)->gapsDe($appraisal);

    expect($gaps)->toHaveCount(1)
        ->and($gaps[0]['codigo'])->toBe('GAP-0001')
        ->and($gaps[0]['practica'])->toBe('PLAN 1.1')
        ->and($gaps[0]['severidad'])->toBeNull();
});
