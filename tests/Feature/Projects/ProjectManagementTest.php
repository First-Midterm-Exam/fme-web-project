<?php

use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectManagement;
use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// =============================================================================
// a) Admin: crear proyecto, asignar integrante, cerrar y verificar solo lectura
// =============================================================================

test('admin can create a project and it appears in the listing', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin);

    Livewire::test(ProjectManagement::class)
        ->call('openCreateModal')
        ->set('name', 'Sistema CMMI')
        ->set('code', 'PROJ-001')
        ->set('startDate', '2026-01-15')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'name' => 'Sistema CMMI',
        'code' => 'PROJ-001',
        'status' => 'activo',
    ]);
});

test('admin can assign and remove a member from a project', function () {
    $admin = User::factory()->administrador()->create();
    $member = User::factory()->colaborador()->create();

    $project = Project::create([
        'name' => 'Proyecto Test',
        'code' => 'PROJ-002',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $this->actingAs($admin);

    // Assign
    Livewire::test(ProjectManagement::class)
        ->call('openMembersModal', $project->id)
        ->set('selectedUserId', $member->id)
        ->call('addMember');

    $this->assertDatabaseHas('project_user', [
        'project_id' => $project->id,
        'user_id' => $member->id,
    ]);

    // Remove
    Livewire::test(ProjectManagement::class)
        ->call('openMembersModal', $project->id)
        ->call('removeMember', $member->id);

    $this->assertDatabaseMissing('project_user', [
        'project_id' => $project->id,
        'user_id' => $member->id,
    ]);
});

test('admin can close a project and it becomes read only', function () {
    $admin = User::factory()->administrador()->create();

    $project = Project::create([
        'name' => 'Proyecto Para Cerrar',
        'code' => 'PROJ-003',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $this->actingAs($admin);

    Livewire::test(ProjectManagement::class)
        ->call('closeProject', $project->id);

    $project->refresh();
    expect($project->status)->toBe('cerrado');
    expect($project->isActive())->toBeFalse();

    // Verify it cannot be edited when closed
    $this->assertFalse((new ProjectPolicy)->update($admin, $project));
    $this->assertFalse((new ProjectPolicy)->manageMembers($admin, $project));
    $this->assertFalse((new ProjectPolicy)->close($admin, $project));
});

// =============================================================================
// b) Non-admin receives 403 when trying to create / edit / close
// =============================================================================

test('project manager cannot create a project and receives 403', function () {
    $pm = User::factory()->jefeProyecto()->create();

    $this->actingAs($pm);

    Livewire::test(ProjectManagement::class)
        ->call('openCreateModal')
        ->assertForbidden();
});

test('contributor cannot create a project and receives 403', function () {
    $contributor = User::factory()->colaborador()->create();

    $this->actingAs($contributor);

    Livewire::test(ProjectManagement::class)
        ->call('openCreateModal')
        ->assertForbidden();
});

test('process manager cannot create a project and receives 403', function () {
    $pm = User::factory()->gestorProcesos()->create();

    $project = Project::create([
        'name' => 'Proyecto Test',
        'code' => 'PROJ-010',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $this->actingAs($pm);

    Livewire::test(ProjectManagement::class)
        ->call('closeProject', $project->id)
        ->assertForbidden();
});

// =============================================================================
// c) Project Manager sees only assigned projects
// =============================================================================

test('project manager only sees projects they are assigned to in the listing', function () {
    $admin = User::factory()->administrador()->create();
    $pm = User::factory()->jefeProyecto()->create();

    $assignedProject = Project::create([
        'name' => 'Proyecto Asignado',
        'code' => 'PROJ-004',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $otherProject = Project::create([
        'name' => 'Proyecto Ajeno',
        'code' => 'PROJ-005',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $assignedProject->users()->attach($pm->id);

    $this->actingAs($pm);

    $visible = Project::visibleFor($pm)->pluck('id');

    expect($visible)->toContain($assignedProject->id)
        ->and($visible)->not->toContain($otherProject->id);
});

// =============================================================================
// d) Project Manager receives 403 on direct URL access to unassigned project
// =============================================================================

test('project manager receives 403 when accessing unassigned project detail directly', function () {
    $pm = User::factory()->jefeProyecto()->create();

    $project = Project::create([
        'name' => 'Proyecto Ajeno',
        'code' => 'PROJ-006',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    // PM is NOT assigned to this project
    $this->actingAs($pm);

    Livewire::test(ProjectDetail::class, ['project' => $project])
        ->assertForbidden();
});

test('contributor receives 403 when accessing unassigned project detail directly', function () {
    $contributor = User::factory()->colaborador()->create();

    $project = Project::create([
        'name' => 'Proyecto Ajeno',
        'code' => 'PROJ-007',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $this->actingAs($contributor);

    Livewire::test(ProjectDetail::class, ['project' => $project])
        ->assertForbidden();
});

// =============================================================================
// e) Process Manager sees all projects but cannot edit
// =============================================================================

test('process manager sees all projects in the listing', function () {
    $processMgr = User::factory()->gestorProcesos()->create();

    $project1 = Project::create([
        'name' => 'Proyecto Uno',
        'code' => 'PROJ-008',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $project2 = Project::create([
        'name' => 'Proyecto Dos',
        'code' => 'PROJ-009',
        'start_date' => '2026-02-01',
        'status' => 'activo',
    ]);

    // Process Manager is NOT assigned to either project
    $this->actingAs($processMgr);

    $visible = Project::visibleFor($processMgr)->pluck('id');

    expect($visible)->toContain($project1->id)
        ->and($visible)->toContain($project2->id);
});

test('process manager cannot edit or close a project', function () {
    $processMgr = User::factory()->gestorProcesos()->create();

    $project = Project::create([
        'name' => 'Proyecto Test',
        'code' => 'PROJ-011',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $policy = new ProjectPolicy;

    expect($policy->update($processMgr, $project))->toBeFalse()
        ->and($policy->close($processMgr, $project))->toBeFalse()
        ->and($policy->create($processMgr))->toBeFalse()
        ->and($policy->view($processMgr, $project))->toBeTrue();
});
