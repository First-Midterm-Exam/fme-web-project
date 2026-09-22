<?php

use App\Actions\Evidences\VerifyEvidenceAction;
use App\Livewire\Evidences\PendingEvidenceVerification;
use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\EvidenceVersion;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\EvidenceStatusSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([
        RoleSeeder::class,
        EvidenceStatusSeeder::class,
    ]);
});

function createProjectForVerification(array $users = []): Project
{
    $project = Project::create([
        'name' => 'Proyecto Test Verification',
        'code' => 'PROJ-'.rand(1000, 9999),
        'start_date' => now()->toDateString(),
        'status' => 'activo',
    ]);

    if (! empty($users)) {
        $project->users()->attach(collect($users)->pluck('id'));
    }

    Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal CMMI Active',
        'domain' => 'Desarrollo',
        'target_level' => 2,
        'target_date' => now()->addMonths(6)->toDateString(),
        'status' => Appraisal::STATUS_ACTIVO,
    ]);

    return $project;
}

function createPendingEvidence(Project $project, User $uploader): Evidence
{
    $evidence = Evidence::create([
        'code' => Evidence::generateNextCode(),
        'project_id' => $project->id,
        'name' => 'Evidencia Pendiente Test',
        'type' => Evidence::TYPE_ACTA,
        'description' => 'Descripción test',
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $uploader->id,
    ]);

    EvidenceVersion::create([
        'evidence_id' => $evidence->id,
        'number' => 1,
        'file_public_id' => 'test/file1',
        'file_url' => 'https://res.cloudinary.com/demo/raw/upload/v1/test/file1.pdf',
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'acta_reunion.pdf',
        'file_size' => 1024,
        'uploaded_by' => $uploader->id,
        'uploaded_at' => now(),
    ]);

    return $evidence;
}

test('gestor de procesos asignado verifica evidencia como verificado -> actualiza estado, verified_by, verified_at y desaparece de pendientes', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();
    $project = createProjectForVerification([$gestor, $uploader]);
    $evidence = createPendingEvidence($project, $uploader);

    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->call('openVerifyModal', $evidence->id)
        ->set('verificationStatus', EvidenceStatus::VERIFICADA)
        ->set('verificationReason', 'Aprobado sin observaciones.')
        ->call('submitVerification')
        ->assertHasNoErrors();

    $evidence->refresh();
    expect($evidence->status_id)->toBe(EvidenceStatus::VERIFICADA)
        ->and($evidence->verified_by)->toBe($gestor->id)
        ->and($evidence->verified_at)->not()->toBeNull()
        ->and($evidence->verification_reason)->toBe('Aprobado sin observaciones.');

    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->assertViewHas('pendingEvidences', function ($pendingEvidences) use ($evidence): bool {
            return ! $pendingEvidences->contains('id', $evidence->id);
        });
});

test('observar evidencia sin motivo falla con mensaje de validacion', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();
    $project = createProjectForVerification([$gestor, $uploader]);
    $evidence = createPendingEvidence($project, $uploader);

    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->call('openVerifyModal', $evidence->id)
        ->set('verificationStatus', EvidenceStatus::OBSERVADA)
        ->set('verificationReason', '')
        ->call('submitVerification')
        ->assertHasErrors(['verificationReason']);

    $evidence->refresh();
    expect($evidence->status_id)->toBe(EvidenceStatus::REGISTRADA)
        ->and($evidence->verified_by)->toBeNull();
});

test('rechazar evidencia sin motivo falla con mensaje de validacion', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();
    $project = createProjectForVerification([$gestor, $uploader]);
    $evidence = createPendingEvidence($project, $uploader);

    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->call('openVerifyModal', $evidence->id)
        ->set('verificationStatus', EvidenceStatus::RECHAZADA)
        ->set('verificationReason', '   ')
        ->call('submitVerification')
        ->assertHasErrors(['verificationReason']);

    $evidence->refresh();
    expect($evidence->status_id)->toBe(EvidenceStatus::REGISTRADA)
        ->and($evidence->verified_by)->toBeNull();
});

test('evaluar como observado o rechazado con motivo exitoso guarda la justificacion', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();
    $project = createProjectForVerification([$gestor, $uploader]);
    $evidence = createPendingEvidence($project, $uploader);

    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->call('openVerifyModal', $evidence->id)
        ->set('verificationStatus', EvidenceStatus::OBSERVADA)
        ->set('verificationReason', 'El documento no incluye firmas requeridas.')
        ->call('submitVerification')
        ->assertHasNoErrors();

    $evidence->refresh();
    expect($evidence->status_id)->toBe(EvidenceStatus::OBSERVADA)
        ->and($evidence->verification_reason)->toBe('El documento no incluye firmas requeridas.')
        ->and($evidence->verified_by)->toBe($gestor->id);
});

test('usuario sin acceso al proyecto no ve la evidencia en la lista pendiente ni puede verificarla', function (): void {
    $gestorA = User::factory()->gestorProcesos()->create();
    $gestorB = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();

    $projectA = createProjectForVerification([$gestorA, $uploader]);
    $evidenceA = createPendingEvidence($projectA, $uploader);

    Livewire::actingAs($gestorB)
        ->test(PendingEvidenceVerification::class)
        ->assertViewHas('pendingEvidences', function ($pendingEvidences) use ($evidenceA): bool {
            return ! $pendingEvidences->contains('id', $evidenceA->id);
        })
        ->call('openVerifyModal', $evidenceA->id)
        ->assertForbidden();
});

test('evidencia que no esta en estado Registrada no se puede verificar nuevamente', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();
    $project = createProjectForVerification([$gestor, $uploader]);

    $evidence = createPendingEvidence($project, $uploader);
    $evidence->update([
        'status_id' => EvidenceStatus::VERIFICADA,
        'verified_by' => $gestor->id,
        'verified_at' => now(),
    ]);

    $action = app(VerifyEvidenceAction::class);

    $this->expectException(ValidationException::class);

    $action->execute($evidence, $gestor, EvidenceStatus::OBSERVADA, 'Intento de re-verificar');
});

test('lista pendiente solo muestra evidencias en estado Registrada de proyectos asignados (Admin ve todos los proyectos)', function (): void {
    $admin = User::factory()->administrador()->create();
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();

    $projectAssigned = createProjectForVerification([$gestor, $uploader]);
    $evidence1 = createPendingEvidence($projectAssigned, $uploader);

    $projectUnassigned = createProjectForVerification([$uploader]);
    $evidence2 = createPendingEvidence($projectUnassigned, $uploader);

    // Gestor solo ve projectAssigned
    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->assertViewHas('pendingEvidences', function ($pendingEvidences) use ($evidence1, $evidence2): bool {
            return $pendingEvidences->contains('id', $evidence1->id) && ! $pendingEvidences->contains('id', $evidence2->id);
        });

    // Admin ve ambas evidencias
    Livewire::actingAs($admin)
        ->test(PendingEvidenceVerification::class)
        ->assertViewHas('pendingEvidences', function ($pendingEvidences) use ($evidence1, $evidence2): bool {
            return $pendingEvidences->contains('id', $evidence1->id) && $pendingEvidences->contains('id', $evidence2->id);
        });
});

test('la verificacion se aplica sobre la evidencia vigente (la mas reciente) en evidencias con multiples versiones', function (): void {
    $gestor = User::factory()->gestorProcesos()->create();
    $uploader = User::factory()->colaborador()->create();
    $project = createProjectForVerification([$gestor, $uploader]);
    $evidence = createPendingEvidence($project, $uploader);

    // Agregar version 2
    EvidenceVersion::create([
        'evidence_id' => $evidence->id,
        'number' => 2,
        'file_public_id' => 'test/file2',
        'file_url' => 'https://res.cloudinary.com/demo/raw/upload/v1/test/file2.pdf',
        'file_resource_type' => 'raw',
        'file_format' => 'pdf',
        'file_original_name' => 'acta_reunion_v2.pdf',
        'file_size' => 2048,
        'uploaded_by' => $uploader->id,
        'uploaded_at' => now(),
    ]);

    expect($evidence->currentVersion->number)->toBe(2);

    Livewire::actingAs($gestor)
        ->test(PendingEvidenceVerification::class)
        ->call('openVerifyModal', $evidence->id)
        ->set('verificationStatus', EvidenceStatus::VERIFICADA)
        ->set('verificationReason', 'Versión 2 aceptada.')
        ->call('submitVerification')
        ->assertHasNoErrors();

    $evidence->refresh();
    expect($evidence->status_id)->toBe(EvidenceStatus::VERIFICADA)
        ->and($evidence->currentVersion->number)->toBe(2)
        ->and($evidence->currentVersion->file_original_name)->toBe('acta_reunion_v2.pdf');
});
