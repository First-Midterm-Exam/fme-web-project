<?php

use App\Actions\Evidences\RegisterEvidenceAction;
use App\Livewire\Evidences\EvidenceManagement;
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
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        EvidenceStatusSeeder::class,
    ]);
});

function createProjectWithAppraisal(string $appraisalStatus = Appraisal::STATUS_ACTIVO, array $users = []): Project
{
    $project = Project::create([
        'name' => 'Proyecto Demo CMMI',
        'code' => 'PROJ-'.rand(1000, 9999),
        'start_date' => now()->toDateString(),
        'status' => 'activo',
    ]);

    if (! empty($users)) {
        $project->users()->attach(collect($users)->pluck('id'));
    }

    Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal CMMI Nivel 2',
        'domain' => 'Desarrollo',
        'target_level' => 2,
        'target_date' => now()->addMonths(6)->toDateString(),
        'status' => $appraisalStatus,
    ]);

    return $project;
}

function makeCloudinaryResponse(array $data): ApiResponse
{
    return new ApiResponse($data, []);
}

test('registrar evidencia con archivo valido queda en estado registrada con codigo autogenerado y version 1', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);

    $cloudinaryResponse = makeCloudinaryResponse([
        'public_id' => "evidencias/{$project->id}/acta_reunion_123",
        'resource_type' => 'raw',
        'format' => 'pdf',
        'bytes' => 2048,
    ]);

    $uploadApiMock = Mockery::mock(UploadApi::class);
    $uploadApiMock->shouldReceive('upload')
        ->once()
        ->andReturn($cloudinaryResponse);

    Cloudinary::shouldReceive('uploadApi')->andReturn($uploadApiMock);

    $this->actingAs($contributor);

    $file = UploadedFile::fake()->create('acta_reunion.pdf', 500, 'application/pdf');

    Livewire::test(EvidenceManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $project->id)
        ->set('name', 'Acta de Inicio de Proyecto')
        ->set('type', Evidence::TYPE_ACTA)
        ->set('description', 'Acta firmada de la reunión de arranque.')
        ->set('file', $file)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('evidences', [
        'code' => 'EV-0001',
        'project_id' => $project->id,
        'name' => 'Acta de Inicio de Proyecto',
        'type' => Evidence::TYPE_ACTA,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $contributor->id,
    ]);

    $evidence = Evidence::where('code', 'EV-0001')->firstOrFail();

    $this->assertDatabaseHas('evidence_versions', [
        'evidence_id' => $evidence->id,
        'number' => 1,
        'file_public_id' => "evidencias/{$project->id}/acta_reunion_123",
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'acta_reunion.pdf',
    ]);
});

test('un archivo de mas de 10 MB es rechazado con mensaje de validacion', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);

    $this->actingAs($contributor);

    $largeFile = UploadedFile::fake()->create('archivo_gigante.pdf', 12000, 'application/pdf');

    Livewire::test(EvidenceManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $project->id)
        ->set('name', 'Archivo Pesado')
        ->set('type', Evidence::TYPE_INFORME)
        ->set('file', $largeFile)
        ->call('save')
        ->assertHasErrors('file');
});

test('un archivo de tipo no permitido es rechazado', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);

    $this->actingAs($contributor);

    $invalidFile = UploadedFile::fake()->create('script_malicioso.exe', 500, 'application/x-msdownload');

    Livewire::test(EvidenceManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $project->id)
        ->set('name', 'Script Ejecutable')
        ->set('type', Evidence::TYPE_OTRO)
        ->set('file', $invalidFile)
        ->call('save')
        ->assertHasErrors('file');
});

test('un usuario sin sesion o sin permiso sobre ese proyecto recibe 403 al pedir la descarga', function (): void {
    $contributorAssigned = User::factory()->colaborador()->create();
    $contributorOther = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributorAssigned]);

    $evidence = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $project->id,
        'name' => 'Evidencia Privada',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $contributorAssigned->id,
    ]);

    EvidenceVersion::create([
        'evidence_id' => $evidence->id,
        'number' => 1,
        'file_public_id' => 'evidencias/1/privado_123',
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'plan.pdf',
        'file_size' => 1024,
        'uploaded_at' => now(),
    ]);

    // 1. Unauthenticated request -> expect 403 Forbidden (no redirect to login)
    $responseGuest = $this->get(route('evidencias.download', $evidence));
    $responseGuest->assertForbidden();

    // 2. Authenticated user without project permission -> expect 403 Forbidden
    $this->actingAs($contributorOther);
    $responseUnauthorized = $this->get(route('evidencias.download', $evidence));
    $responseUnauthorized->assertForbidden();
});

test('un usuario autorizado recibe una url temporal de descarga con 5 minutos de expiracion', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);

    $evidence = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $project->id,
        'name' => 'Plan de Calidad',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $contributor->id,
    ]);

    EvidenceVersion::create([
        'evidence_id' => $evidence->id,
        'number' => 1,
        'file_public_id' => 'evidencias/1/plan_calidad_123',
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'plan_calidad.pdf',
        'file_size' => 2048,
        'uploaded_at' => now(),
    ]);

    $expectedDownloadUrl = 'https://res.cloudinary.com/demo/raw/authenticated/s--signature--/v1/evidencias/1/plan_calidad_123.pdf?expires_at='.(time() + 300);

    $uploadApiMock = Mockery::mock(UploadApi::class);
    $uploadApiMock->shouldReceive('privateDownloadUrl')
        ->once()
        ->with(
            'evidencias/1/plan_calidad_123',
            'pdf',
            Mockery::on(function (array $options): bool {
                return ($options['type'] ?? '') === 'authenticated'
                    && ($options['resource_type'] ?? '') === 'raw'
                    && isset($options['expires_at'])
                    && $options['expires_at'] >= (time() + 290)
                    && $options['expires_at'] <= (time() + 310);
            })
        )
        ->andReturn($expectedDownloadUrl);

    Cloudinary::shouldReceive('uploadApi')->andReturn($uploadApiMock);

    $this->actingAs($contributor);

    $response = $this->get(route('evidencias.download', $evidence));
    $response->assertRedirect($expectedDownloadUrl);
});

test('no se puede registrar una evidencia en un proyecto sin appraisal en curso RN-10', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $projectActive = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);
    $projectNoActiveAppraisal = createProjectWithAppraisal(Appraisal::STATUS_BORRADOR, [$contributor]);

    $this->actingAs($contributor);

    $file = UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf');

    Livewire::test(EvidenceManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $projectNoActiveAppraisal->id)
        ->set('name', 'Intento Registro Sin Appraisal Activo')
        ->set('type', Evidence::TYPE_REGISTRO)
        ->set('file', $file)
        ->call('save')
        ->assertHasErrors(['projectId']);
});

test('dos evidencias consecutivas reciben EV-0001 y EV-0002', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);

    $cloudinaryResponse = makeCloudinaryResponse([
        'public_id' => 'evidencias/1/mock_file',
        'resource_type' => 'raw',
        'format' => 'pdf',
        'bytes' => 1024,
    ]);

    $uploadApiMock = Mockery::mock(UploadApi::class);
    $uploadApiMock->shouldReceive('upload')
        ->twice()
        ->andReturn($cloudinaryResponse);

    Cloudinary::shouldReceive('uploadApi')->andReturn($uploadApiMock);

    $this->actingAs($contributor);

    $file1 = UploadedFile::fake()->create('doc1.pdf', 500, 'application/pdf');
    $file2 = UploadedFile::fake()->create('doc2.pdf', 500, 'application/pdf');

    Livewire::test(EvidenceManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $project->id)
        ->set('name', 'Primera Evidencia')
        ->set('type', Evidence::TYPE_ACTA)
        ->set('file', $file1)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(EvidenceManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $project->id)
        ->set('name', 'Segunda Evidencia')
        ->set('type', Evidence::TYPE_INFORME)
        ->set('file', $file2)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('evidences', ['code' => 'EV-0001', 'name' => 'Primera Evidencia']);
    $this->assertDatabaseHas('evidences', ['code' => 'EV-0002', 'name' => 'Segunda Evidencia']);
});

test('si la subida a Cloudinary falla no se crea ninguna evidencia', function (): void {
    $contributor = User::factory()->colaborador()->create();
    $project = createProjectWithAppraisal(Appraisal::STATUS_ACTIVO, [$contributor]);

    $uploadApiMock = Mockery::mock(UploadApi::class);
    $uploadApiMock->shouldReceive('upload')
        ->once()
        ->andThrow(new Exception('Falló la conexión con Cloudinary'));

    Cloudinary::shouldReceive('uploadApi')->andReturn($uploadApiMock);

    $action = app(RegisterEvidenceAction::class);
    $file = UploadedFile::fake()->create('evidencia.pdf', 500, 'application/pdf');

    expect(fn () => $action->execute(
        $project,
        $contributor,
        $file,
        ['name' => 'Evidencia Fallida', 'type' => Evidence::TYPE_ACTA]
    ))->toThrow(Exception::class, 'Falló la conexión con Cloudinary');

    $this->assertDatabaseCount('evidences', 0);
    $this->assertDatabaseCount('evidence_versions', 0);
});
