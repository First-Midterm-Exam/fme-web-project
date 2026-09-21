<?php

use App\Livewire\Appraisals\AppraisalManagement;
use App\Models\Appraisal;
use App\Models\Project;
use App\Models\User;
use App\Policies\AppraisalPolicy;
use App\Services\AppraisalStateService;
use Database\Seeders\RoleSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// =============================================================================
// a) Crear en borrador -> activar -> verificar selector bloqueado -> cerrar -> solo lectura
// =============================================================================

test('admin can create appraisal in borrador, activate it, and close it becoming read-only', function () {
    $admin = User::factory()->administrador()->create();
    $project = Project::create([
        'name' => 'Proyecto Activo Alpha',
        'code' => 'PROJ-ALP',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $this->actingAs($admin);

    // 1. Create in borrador
    Livewire::test(AppraisalManagement::class)
        ->call('openCreateModal')
        ->set('projectId', $project->id)
        ->set('name', 'Appraisal CMMI Nivel 3')
        ->set('domain', 'Development')
        ->set('targetLevel', 3)
        ->set('targetDate', '2026-12-31')
        ->call('save')
        ->assertHasNoErrors();

    $appraisal = Appraisal::where('name', 'Appraisal CMMI Nivel 3')->firstOrFail();
    expect($appraisal->status)->toBe('borrador')
        ->and($appraisal->project_id)->toBe($project->id);

    // 2. Activate appraisal
    Livewire::test(AppraisalManagement::class)
        ->call('activateAppraisal', $appraisal->id);

    $appraisal->refresh();
    expect($appraisal->status)->toBe('activo');

    // 3. Verify project is locked: update policy rejects editing an active appraisal
    $policy = new AppraisalPolicy;
    expect($policy->update($admin, $appraisal))->toBeFalse()
        ->and($policy->updateStatus($admin, $appraisal))->toBeTrue();

    // 4. Close appraisal
    Livewire::test(AppraisalManagement::class)
        ->call('closeAppraisal', $appraisal->id);

    $appraisal->refresh();
    expect($appraisal->status)->toBe('cerrado');

    // 5. Verify policy marks it non-editable and non-status-updatable once closed
    $policy = new AppraisalPolicy;
    expect($policy->update($admin, $appraisal))->toBeFalse()
        ->and($policy->updateStatus($admin, $appraisal))->toBeFalse();
});

// =============================================================================
// b) Intentar editar el proyecto de un appraisal activo debe fallar (validación server-side)
// =============================================================================

test('editing the project of an active appraisal fails server-side validation', function () {
    $admin = User::factory()->administrador()->create();
    $project1 = Project::create([
        'name' => 'Proyecto 1',
        'code' => 'P1',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $project2 = Project::create([
        'name' => 'Proyecto 2',
        'code' => 'P2',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project1->id,
        'name' => 'Appraisal Activo',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-12-31',
        'status' => 'activo',
    ]);

    $this->actingAs($admin);

    // Service validation throws ValidationException
    $service = new AppraisalStateService;
    expect(fn () => $service->validateProjectChange($appraisal, $project2->id))
        ->toThrow(ValidationException::class);
});

// =============================================================================
// c) Un Jefe de Proyecto ve solo los appraisals de sus proyectos asignados
// =============================================================================

test('project manager only sees appraisals of their assigned projects', function () {
    $pm = User::factory()->jefeProyecto()->create();

    $assignedProject = Project::create([
        'name' => 'Proyecto Asignado PM',
        'code' => 'PAPM',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $assignedProject->users()->attach($pm->id);

    $unassignedProject = Project::create([
        'name' => 'Proyecto Ajeno PM',
        'code' => 'PAJPM',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $myAppraisal = Appraisal::create([
        'project_id' => $assignedProject->id,
        'name' => 'Mi Appraisal',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-06-30',
        'status' => 'borrador',
    ]);

    $otherAppraisal = Appraisal::create([
        'project_id' => $unassignedProject->id,
        'name' => 'Appraisal Ajeno',
        'domain' => 'Services',
        'target_level' => 3,
        'target_date' => '2026-08-31',
        'status' => 'borrador',
    ]);

    $visible = Appraisal::visibleFor($pm)->pluck('id');

    expect($visible)->toContain($myAppraisal->id)
        ->and($visible)->not->toContain($otherAppraisal->id);
});

// =============================================================================
// d) Un Jefe de Proyecto o Colaborador recibe 403 al pedir appraisal de un proyecto ajeno
// =============================================================================

test('project manager and contributor receive 403 when accessing appraisal of an unassigned project', function () {
    $pm = User::factory()->jefeProyecto()->create();
    $colaborador = User::factory()->colaborador()->create();

    $unassignedProject = Project::create([
        'name' => 'Proyecto No Asignado',
        'code' => 'PNA',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $unassignedProject->id,
        'name' => 'Appraisal Restringido',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-10-31',
        'status' => 'activo',
    ]);

    $policy = new AppraisalPolicy;

    expect($policy->view($pm, $appraisal))->toBeFalse()
        ->and($policy->view($colaborador, $appraisal))->toBeFalse();
});

// =============================================================================
// e) Un Gestor de Procesos puede cambiar el estado de un appraisal pero no crearlo
// =============================================================================

test('process manager can change appraisal status but receives 403 when creating', function () {
    $gestor = User::factory()->gestorProcesos()->create();
    $project = Project::create([
        'name' => 'Proyecto CMMI',
        'code' => 'PCMMI',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $appraisal = Appraisal::create([
        'project_id' => $project->id,
        'name' => 'Appraisal para Activar',
        'domain' => 'Development',
        'target_level' => 3,
        'target_date' => '2026-11-30',
        'status' => 'borrador',
    ]);

    $this->actingAs($gestor);

    // Can change status (borrador -> activo)
    Livewire::test(AppraisalManagement::class)
        ->call('activateAppraisal', $appraisal->id);

    expect($appraisal->fresh()->status)->toBe('activo');

    // Cannot create (receives 403)
    Livewire::test(AppraisalManagement::class)
        ->call('openCreateModal')
        ->assertForbidden();

    $policy = new AppraisalPolicy;
    expect($policy->create($gestor))->toBeFalse()
        ->and($policy->updateStatus($gestor, $appraisal))->toBeTrue();
});

// =============================================================================
// f) No se puede crear un appraisal sobre un proyecto cerrado
// =============================================================================

test('cannot create an appraisal on a closed project', function () {
    $admin = User::factory()->administrador()->create();
    $closedProject = Project::create([
        'name' => 'Proyecto Ya Cerrado',
        'code' => 'PYC',
        'start_date' => '2026-01-01',
        'status' => 'cerrado',
    ]);

    $this->actingAs($admin);

    // ValidationException thrown
    $service = new AppraisalStateService;
    expect(fn () => $service->validateCreation($closedProject->id))
        ->toThrow(ValidationException::class);
});
