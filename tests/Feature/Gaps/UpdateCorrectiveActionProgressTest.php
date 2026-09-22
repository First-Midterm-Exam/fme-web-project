<?php

use App\Actions\Gaps\UpdateCorrectiveActionProgressAction;
use App\Livewire\Gaps\GapDetail;
use App\Models\Appraisal;
use App\Models\CorrectiveAction;
use App\Models\Gap;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        EvidenceStatusSeeder::class,
        CmmiCatalogSeeder::class,
    ]);
});

function makeCloudinaryResponseForAction(array $data): ApiResponse
{
    return new ApiResponse($data, []);
}

function mockCloudinaryUploadForAction(string $publicId = 'evidencias/test_solution'): void
{
    $mockApi = Mockery::mock(UploadApi::class);
    $mockApi->shouldReceive('upload')
        ->andReturn(makeCloudinaryResponseForAction([
            'public_id' => $publicId,
            'secure_url' => 'https://res.cloudinary.com/demo/raw/authenticated/'.$publicId.'.pdf',
            'resource_type' => 'raw',
            'format' => 'pdf',
        ]));

    Cloudinary::shouldReceive('uploadApi')->andReturn($mockApi);
}

function createGapWithAction(string $gapStatus = Gap::STATUS_EN_PROGRESO, ?User $responsible = null, int $initialProgress = 0): array
{
    $projectManager = User::factory()->jefeProyecto()->create();
    $responsible = $responsible ?? User::factory()->colaborador()->create();

    $project = Project::create([
        'name' => 'Proyecto Test HU-19',
        'code' => 'PROJ-'.rand(1000, 9999),
        'start_date' => now()->toDateString(),
        'status' => 'activo',
    ]);
    $project->users()->attach([$projectManager->id, $responsible->id]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal HU-19',
        'domain' => 'Desarrollo',
        'target_level' => 2,
        'target_date' => now()->addMonths(6)->toDateString(),
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    $evaluation = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => 1,
        'status' => 'No cumple',
    ]);

    $gap = Gap::create([
        'code' => Gap::generateNextCode(),
        'practice_evaluation_id' => $evaluation->id,
        'title' => 'Gap de prueba HU-19',
        'description' => 'Descripción del gap',
        'status' => $gapStatus,
        'severity' => Gap::SEVERITY_MEDIA,
        'assigned_to_id' => $responsible->id,
        'due_date' => now()->addDays(10)->toDateString(),
        'generated_by' => $projectManager->id,
    ]);

    $actionStatus = $initialProgress > 0 ? CorrectiveAction::STATUS_EN_PROGRESO : CorrectiveAction::STATUS_ABIERTA;

    $action = CorrectiveAction::create([
        'gap_id' => $gap->id,
        'description' => 'Acción correctiva de prueba',
        'responsible_id' => $responsible->id,
        'due_date' => now()->addDays(7)->toDateString(),
        'progress_percent' => $initialProgress,
        'status' => $actionStatus,
    ]);

    return [$gap, $action, $responsible, $projectManager, $project];
}

test('a) actualizar a un valor intermedio (40%) sin evidencia se guarda y status pasa a en_progreso', function (): void {
    [$gap, $action, $responsible] = createGapWithAction(Gap::STATUS_EN_PROGRESO, null, 0);

    expect($action->status)->toBe(CorrectiveAction::STATUS_ABIERTA);

    Livewire::actingAs($responsible)
        ->test(GapDetail::class, ['gap' => $gap])
        ->call('openUpdateProgressModal')
        ->set('progress_percent', 40)
        ->set('progress_comment', 'Avance del 40% realizado')
        ->call('updateProgress')
        ->assertHasNoErrors();

    $action->refresh();
    expect($action->progress_percent)->toBe(40)
        ->and($action->status)->toBe(CorrectiveAction::STATUS_EN_PROGRESO);
});

test('b) intentar 100% sin evidencia es rechazado con mensaje de validacion', function (): void {
    [$gap, $action, $responsible] = createGapWithAction(Gap::STATUS_EN_PROGRESO, null, 0);

    Livewire::actingAs($responsible)
        ->test(GapDetail::class, ['gap' => $gap])
        ->call('openUpdateProgressModal')
        ->set('progress_percent', 100)
        ->call('updateProgress')
        ->assertHasErrors(['file']);

    $action->refresh();
    $gap->refresh();
    expect($action->progress_percent)->toBe(0)
        ->and($gap->status)->toBe(Gap::STATUS_EN_PROGRESO);
});

test('c) actualizar a 100% con evidencia adjunta funciona y el Gap padre pasa a Resuelto', function (): void {
    mockCloudinaryUploadForAction('evidencias/solucion_404');
    [$gap, $action, $responsible] = createGapWithAction(Gap::STATUS_EN_PROGRESO, null, 0);

    $file = UploadedFile::fake()->create('solucion.pdf', 500, 'application/pdf');

    Livewire::actingAs($responsible)
        ->test(GapDetail::class, ['gap' => $gap])
        ->call('openUpdateProgressModal')
        ->set('progress_percent', 100)
        ->set('solution_file', $file)
        ->set('progress_comment', 'Solución completada y verificada.')
        ->call('updateProgress')
        ->assertHasNoErrors();

    $action->refresh();
    $gap->refresh();

    expect($action->progress_percent)->toBe(100)
        ->and($action->solution_evidence_id)->not()->toBeNull()
        ->and($gap->status)->toBe(Gap::STATUS_RESUELTO);
});

test('d) un usuario que no pasa la Policy no puede actualizar el avance', function (): void {
    [$gap, $action, $responsible, $projectManager, $project] = createGapWithAction(Gap::STATUS_EN_PROGRESO);
    $otherUser = User::factory()->colaborador()->create();

    // Usuario fuera del proyecto no puede ni ver el gap
    Livewire::actingAs($otherUser)
        ->test(GapDetail::class, ['gap' => $gap])
        ->assertForbidden();

    // Usuario dentro del proyecto pero no responsable ni admin/gestor puede ver el gap pero no actualizar la acción
    $unassignedMember = User::factory()->colaborador()->create();
    $project->users()->attach($unassignedMember->id);

    Livewire::actingAs($unassignedMember)
        ->test(GapDetail::class, ['gap' => $gap])
        ->call('openUpdateProgressModal')
        ->assertForbidden();

    expect(fn () => app(UpdateCorrectiveActionProgressAction::class)->execute($action, $unassignedMember, 50))
        ->toThrow(AuthorizationException::class);
});

test('e) el log de la accion correctiva registra cada actualizacion con usuario y fecha', function (): void {
    [$gap, $action, $responsible] = createGapWithAction(Gap::STATUS_EN_PROGRESO, null, 0);

    Livewire::actingAs($responsible)
        ->test(GapDetail::class, ['gap' => $gap])
        ->call('openUpdateProgressModal')
        ->set('progress_percent', 30)
        ->set('progress_comment', 'Primer avance parcial')
        ->call('updateProgress')
        ->assertHasNoErrors();

    $action->refresh();
    expect($action->logs)->toHaveCount(1);

    $log = $action->logs->first();
    expect($log->user_id)->toBe($responsible->id)
        ->and($log->field)->toBe('progress_percent')
        ->and($log->old_value)->toBe('0')
        ->and($log->new_value)->toBe('30')
        ->and($log->description)->toBe('Primer avance parcial');
});

test('f) si el Gap no esta en En progreso, no se puede completar la accion al 100%', function (): void {
    mockCloudinaryUploadForAction('evidencias/solucion_abierto');
    [$gap, $action, $responsible] = createGapWithAction(Gap::STATUS_ABIERTO, null, 0);

    $file = UploadedFile::fake()->create('solucion.pdf', 500, 'application/pdf');

    Livewire::actingAs($responsible)
        ->test(GapDetail::class, ['gap' => $gap])
        ->call('openUpdateProgressModal')
        ->set('progress_percent', 100)
        ->set('solution_file', $file)
        ->call('updateProgress')
        ->assertHasErrors(['gap']);

    $action->refresh();
    $gap->refresh();
    expect($action->progress_percent)->toBe(0)
        ->and($gap->status)->toBe(Gap::STATUS_ABIERTO);
});
