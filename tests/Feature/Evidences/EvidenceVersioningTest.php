<?php

use App\Actions\Evidences\AddEvidenceVersionAction;
use App\Livewire\Evidences\EvidenceDetail;
use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\EvidenceVersion;
use App\Models\Project;
use App\Models\User;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Database\Seeders\EvidenceStatusSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(EvidenceStatusSeeder::class);
});

/**
 * @param  list<User>  $usuarios
 * @return array{0: Evidence, 1: Project}
 */
function evidenciaConVersionInicial(string $estadoAppraisal = Appraisal::STATUS_ACTIVO, array $usuarios = []): array
{
    $proyecto = Project::create([
        'name' => 'Proyecto Versiones',
        'code' => 'P-VER',
        'start_date' => now()->toDateString(),
        'status' => 'activo',
    ]);

    $proyecto->users()->attach(collect($usuarios)->pluck('id'));

    Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => 'Appraisal Versiones',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => now()->addMonths(6)->toDateString(),
        'status' => $estadoAppraisal,
    ]);

    $autor = $usuarios[0] ?? User::factory()->colaborador()->create();

    $evidencia = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $proyecto->id,
        'name' => 'Plan de proyecto',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $autor->id,
    ]);

    EvidenceVersion::create([
        'evidence_id' => $evidencia->id,
        'number' => 1,
        'file_public_id' => "evidencias/{$proyecto->id}/plan_v1",
        'file_url' => "https://res.cloudinary.com/demo/raw/authenticated/evidencias/{$proyecto->id}/plan_v1.pdf",
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'plan_v1.pdf',
        'file_size' => 2048,
        'uploaded_by' => $autor->id,
        'uploaded_at' => now()->subDay(),
    ]);

    return [$evidencia, $proyecto];
}

function simularSubidaDeVersion(string $publicId, int $veces = 1): UploadApi
{
    $subida = Mockery::mock(UploadApi::class);
    $subida->shouldReceive('upload')
        ->times($veces)
        ->andReturn(new ApiResponse([
            'public_id' => $publicId,
            'resource_type' => 'raw',
            'format' => 'pdf',
            'bytes' => 4096,
            'secure_url' => "https://res.cloudinary.com/demo/raw/authenticated/{$publicId}.pdf",
        ], []));

    Cloudinary::shouldReceive('uploadApi')->andReturn($subida);

    return $subida;
}

test('a jefe de proyecto uploads a new version and the previous one is kept', function () {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia, $proyecto] = evidenciaConVersionInicial(usuarios: [$jefe]);
    simularSubidaDeVersion("evidencias/{$proyecto->id}/plan_v2");

    $this->actingAs($jefe);

    Livewire::test(EvidenceDetail::class, ['evidence' => $evidencia])
        ->set('file', UploadedFile::fake()->create('plan_v2.pdf', 300, 'application/pdf'))
        ->call('uploadVersion')
        ->assertHasNoErrors()
        ->assertSee('Se registró la versión 2');

    expect($evidencia->versions()->count())->toBe(2)
        ->and($evidencia->versions()->where('number', 1)->value('file_public_id'))->toBe("evidencias/{$proyecto->id}/plan_v1");

    $this->assertDatabaseHas('evidence_versions', [
        'evidence_id' => $evidencia->id,
        'number' => 2,
        'file_public_id' => "evidencias/{$proyecto->id}/plan_v2",
        'file_url' => "https://res.cloudinary.com/demo/raw/authenticated/evidencias/{$proyecto->id}/plan_v2.pdf",
        'file_original_name' => 'plan_v2.pdf',
        'file_size' => 4096,
        'uploaded_by' => $jefe->id,
    ]);
});

test('the latest version becomes the current one', function () {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia, $proyecto] = evidenciaConVersionInicial(usuarios: [$jefe]);
    simularSubidaDeVersion("evidencias/{$proyecto->id}/plan_nuevo", 2);

    $accion = app(AddEvidenceVersionAction::class);
    $accion->execute($evidencia, $jefe, UploadedFile::fake()->create('plan_v2.pdf', 100, 'application/pdf'));
    $accion->execute($evidencia, $jefe, UploadedFile::fake()->create('plan_v3.pdf', 100, 'application/pdf'));

    expect($evidencia->fresh()->currentVersion->number)->toBe(3)
        ->and($evidencia->versions()->orderBy('number')->pluck('number')->all())->toBe([1, 2, 3]);
});

test('the same roles that register evidences can upload versions', function (string $estado, bool $asignado) {
    $usuario = User::factory()->{$estado}()->create();
    [$evidencia, $proyecto] = evidenciaConVersionInicial(usuarios: $asignado ? [$usuario] : []);
    simularSubidaDeVersion("evidencias/{$proyecto->id}/plan_v2");

    $this->actingAs($usuario);

    Livewire::test(EvidenceDetail::class, ['evidence' => $evidencia])
        ->set('file', UploadedFile::fake()->create('plan_v2.pdf', 100, 'application/pdf'))
        ->call('uploadVersion')
        ->assertHasNoErrors();

    expect($evidencia->versions()->count())->toBe(2);
})->with([
    'administrador' => ['administrador', false],
    'gestor de procesos' => ['gestorProcesos', false],
    'jefe de proyecto asignado' => ['jefeProyecto', true],
    'colaborador asignado' => ['colaborador', true],
]);

test('a user outside the project cannot see nor version the evidence', function (string $estado) {
    [$evidencia] = evidenciaConVersionInicial();

    $this->actingAs(User::factory()->{$estado}()->create());

    $this->get(route('evidencias.show', $evidencia))->assertForbidden();
})->with(['jefeProyecto', 'colaborador']);

test('a version cannot be uploaded without an active appraisal RN-10', function () {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia] = evidenciaConVersionInicial(Appraisal::STATUS_CERRADO, [$jefe]);

    $this->actingAs($jefe);

    Livewire::test(EvidenceDetail::class, ['evidence' => $evidencia])
        ->set('file', UploadedFile::fake()->create('plan_v2.pdf', 100, 'application/pdf'))
        ->call('uploadVersion')
        ->assertForbidden();

    expect(fn () => app(AddEvidenceVersionAction::class)->execute(
        $evidencia,
        $jefe,
        UploadedFile::fake()->create('plan_v2.pdf', 100, 'application/pdf')
    ))->toThrow(ValidationException::class);

    expect($evidencia->versions()->count())->toBe(1);
});

test('an invalid file is rejected and no version is created', function (UploadedFile $archivo) {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia] = evidenciaConVersionInicial(usuarios: [$jefe]);

    $this->actingAs($jefe);

    Livewire::test(EvidenceDetail::class, ['evidence' => $evidencia])
        ->set('file', $archivo)
        ->call('uploadVersion')
        ->assertHasErrors('file');

    expect($evidencia->versions()->count())->toBe(1);
})->with([
    'mayor a 10 MB' => [fn () => UploadedFile::fake()->create('grande.pdf', 12000, 'application/pdf')],
    'tipo no permitido' => [fn () => UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload')],
]);

test('a missing file is rejected', function () {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia] = evidenciaConVersionInicial(usuarios: [$jefe]);

    $this->actingAs($jefe);

    Livewire::test(EvidenceDetail::class, ['evidence' => $evidencia])
        ->call('uploadVersion')
        ->assertHasErrors(['file' => 'required']);
});

test('a failed cloudinary upload keeps the history intact', function () {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia] = evidenciaConVersionInicial(usuarios: [$jefe]);

    $subida = Mockery::mock(UploadApi::class);
    $subida->shouldReceive('upload')->once()->andThrow(new Exception('Falló la conexión con Cloudinary'));
    Cloudinary::shouldReceive('uploadApi')->andReturn($subida);

    $this->actingAs($jefe);

    Livewire::test(EvidenceDetail::class, ['evidence' => $evidencia])
        ->set('file', UploadedFile::fake()->create('plan_v2.pdf', 100, 'application/pdf'))
        ->call('uploadVersion')
        ->assertHasErrors('file');

    expect($evidencia->versions()->count())->toBe(1);
});

test('the detail page shows the whole version history', function () {
    $jefe = User::factory()->jefeProyecto()->create();
    [$evidencia, $proyecto] = evidenciaConVersionInicial(usuarios: [$jefe]);
    simularSubidaDeVersion("evidencias/{$proyecto->id}/plan_v2");

    app(AddEvidenceVersionAction::class)->execute($evidencia, $jefe, UploadedFile::fake()->create('plan_v2.pdf', 100, 'application/pdf'));

    $this->actingAs($jefe);

    $this->get(route('evidencias.show', $evidencia))
        ->assertOk()
        ->assertSeeInOrder(['v2', 'Vigente', 'plan_v2.pdf', 'v1', 'plan_v1.pdf'])
        ->assertSee(route('evidencias.versions.download', [$evidencia, $evidencia->versions()->where('number', 1)->first()]), false);
});

test('each version can be downloaded through a temporary private link', function () {
    $colaborador = User::factory()->colaborador()->create();
    [$evidencia, $proyecto] = evidenciaConVersionInicial(usuarios: [$colaborador]);
    $primera = $evidencia->versions()->where('number', 1)->firstOrFail();

    $subida = Mockery::mock(UploadApi::class);
    $subida->shouldReceive('privateDownloadUrl')
        ->once()
        ->with("evidencias/{$proyecto->id}/plan_v1", 'pdf', Mockery::on(fn (array $opciones): bool => ($opciones['type'] ?? '') === 'authenticated'
            && ($opciones['expires_at'] ?? 0) >= time() + 290))
        ->andReturn('https://res.cloudinary.com/demo/firmado/plan_v1.pdf');
    Cloudinary::shouldReceive('uploadApi')->andReturn($subida);

    $this->actingAs($colaborador);

    $this->get(route('evidencias.versions.download', [$evidencia, $primera]))
        ->assertRedirect('https://res.cloudinary.com/demo/firmado/plan_v1.pdf');
});

test('a version of another evidence answers 404 on download', function () {
    $colaborador = User::factory()->colaborador()->create();
    [$evidencia] = evidenciaConVersionInicial(usuarios: [$colaborador]);

    $otra = Evidence::create([
        'code' => 'EV-0002',
        'project_id' => $evidencia->project_id,
        'name' => 'Otra evidencia',
        'type' => Evidence::TYPE_ACTA,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $colaborador->id,
    ]);
    $ajena = EvidenceVersion::create([
        'evidence_id' => $otra->id,
        'number' => 1,
        'file_public_id' => 'evidencias/otra',
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'otra.pdf',
        'file_size' => 10,
        'uploaded_at' => now(),
    ]);

    $this->actingAs($colaborador);

    $this->get(route('evidencias.versions.download', [$evidencia, $ajena]))->assertNotFound();
});

test('a guest is redirected to login from the evidence detail', function () {
    [$evidencia] = evidenciaConVersionInicial();

    $this->get(route('evidencias.show', $evidencia))->assertRedirect(route('login'));
});

test('the evidence list links each evidence to its version history', function () {
    $colaborador = User::factory()->colaborador()->create();
    [$evidencia] = evidenciaConVersionInicial(usuarios: [$colaborador]);

    $this->actingAs($colaborador);

    $this->get(route('evidencias.index'))
        ->assertOk()
        ->assertSee(route('evidencias.show', $evidencia), false);
});
