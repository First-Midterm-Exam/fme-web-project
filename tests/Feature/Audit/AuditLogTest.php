<?php

use App\Actions\Evidences\VerifyEvidenceAction;
use App\Livewire\Audit\AuditLogList;
use App\Livewire\Projects\ProjectManagement;
use App\Livewire\Users\UserManagement;
use App\Models\Appraisal;
use App\Models\AuditLog;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Livewire\Livewire;

function proyectoAuditado(): Project
{
    return Project::create(['name' => 'Proyecto Auditado', 'code' => 'P-AUD', 'start_date' => '2026-01-01', 'status' => 'activo']);
}

test('creating a record is audited with its author, values and ip', function () {
    $admin = User::factory()->administrador()->create();
    $this->actingAs($admin);

    $proyecto = proyectoAuditado();

    $log = AuditLog::where('auditable_type', Project::class)->where('auditable_id', $proyecto->id)->firstOrFail();

    expect($log->action)->toBe(AuditLog::ACTION_CREATED)
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->entity)->toBe('Proyecto P-AUD')
        ->and($log->old_values)->toBeNull()
        ->and($log->new_values['name'])->toBe('Proyecto Auditado')
        ->and($log->ip_address)->not->toBeNull();
});

test('an update stores only the changed fields with their previous and new values', function () {
    $proyecto = proyectoAuditado();
    $this->actingAs(User::factory()->administrador()->create());

    $proyecto->update(['name' => 'Proyecto Renombrado']);

    $log = AuditLog::where('action', AuditLog::ACTION_UPDATED)->latest('id')->firstOrFail();

    expect($log->old_values)->toBe(['name' => 'Proyecto Auditado'])
        ->and($log->new_values)->toBe(['name' => 'Proyecto Renombrado']);
});

test('saving without real changes is not audited', function () {
    $proyecto = proyectoAuditado();
    $antes = AuditLog::count();

    $proyecto->update(['name' => 'Proyecto Auditado']);

    expect(AuditLog::count())->toBe($antes);
});

test('a deletion keeps the last values of the record', function () {
    $proyecto = proyectoAuditado();

    $proyecto->delete();

    $log = AuditLog::where('action', AuditLog::ACTION_DELETED)->firstOrFail();

    expect($log->old_values['code'])->toBe('P-AUD')
        ->and($log->new_values)->toBeNull();
});

test('passwords and tokens never reach the audit log', function () {
    $usuario = User::factory()->colaborador()->create();

    $usuario->update(['password' => 'OtraClave2026', 'name' => 'Nombre nuevo']);

    AuditLog::where('auditable_type', User::class)->get()->each(function (AuditLog $log): void {
        expect(array_keys($log->old_values ?? []))->not->toContain('password', 'remember_token')
            ->and(array_keys($log->new_values ?? []))->not->toContain('password', 'remember_token');
    });

    expect(AuditLog::where('action', AuditLog::ACTION_UPDATED)->latest('id')->firstOrFail()->new_values)
        ->toBe(['name' => 'Nombre nuevo']);
});

test('verifying an evidence registers the audit entry', function () {
    $this->seed(EvidenceStatusSeeder::class);
    $admin = User::factory()->administrador()->create();
    $this->actingAs($admin);

    $proyecto = proyectoAuditado();
    Appraisal::create(['project_id' => $proyecto->id, 'name' => 'A', 'domain' => 'Development', 'target_level' => 2, 'target_date' => '2026-12-31', 'status' => 'activo']);

    $evidencia = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $proyecto->id,
        'name' => 'Plan',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $admin->id,
    ]);

    app(VerifyEvidenceAction::class)->execute($evidencia, $admin, EvidenceStatus::RECHAZADA, 'Sin firmas.');

    $log = AuditLog::where('auditable_type', Evidence::class)
        ->where('action', AuditLog::ACTION_UPDATED)
        ->firstOrFail();

    expect($log->entity)->toBe('Evidencia EV-0001')
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->old_values['status_id'])->toBe(EvidenceStatus::REGISTRADA)
        ->and($log->new_values['status_id'])->toBe(EvidenceStatus::RECHAZADA)
        ->and($log->new_values['verification_reason'])->toBe('Sin firmas.');
});

test('changing a user role is audited', function () {
    $admin = User::factory()->administrador()->create();
    $usuario = User::factory()->colaborador()->create();
    $this->actingAs($admin);

    Livewire::test(UserManagement::class)
        ->call('openEditModal', $usuario->id)
        ->set('role', 'Jefe de Proyecto')
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->firstOrFail();

    expect($log->auditable_id)->toBe($usuario->id)
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->old_values)->toBe(['rol' => 'Colaborador'])
        ->and($log->new_values)->toBe(['rol' => 'Jefe de Proyecto']);
});

test('editing a user without changing the role does not log a role change', function () {
    $usuario = User::factory()->colaborador()->create();
    $this->actingAs(User::factory()->administrador()->create());

    Livewire::test(UserManagement::class)
        ->call('openEditModal', $usuario->id)
        ->set('name', 'Otro nombre')
        ->call('save');

    expect(AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->count())->toBe(0);
});

test('creating a user audits the assigned role', function () {
    $this->actingAs(User::factory()->administrador()->create());

    Livewire::test(UserManagement::class)
        ->call('openCreateModal')
        ->set('name', 'Nueva Persona')
        ->set('email', 'nueva@dima.cl')
        ->set('password', 'password123')
        ->set('role', 'Gestor de Procesos')
        ->call('save')
        ->assertHasNoErrors();

    expect(AuditLog::where('action', AuditLog::ACTION_ROLE_CHANGED)->firstOrFail()->new_values)
        ->toBe(['rol' => 'Gestor de Procesos']);
});

test('adding and removing project members is audited', function () {
    $admin = User::factory()->administrador()->create();
    $integrante = User::factory()->jefeProyecto()->create(['email' => 'integrante@dima.cl']);
    $proyecto = proyectoAuditado();
    $this->actingAs($admin);

    Livewire::test(ProjectManagement::class)
        ->call('openMembersModal', $proyecto->id)
        ->set('selectedUserId', $integrante->id)
        ->call('addMember')
        ->call('removeMember', $integrante->id);

    $agregado = AuditLog::where('action', AuditLog::ACTION_MEMBER_ADDED)->firstOrFail();
    $retirado = AuditLog::where('action', AuditLog::ACTION_MEMBER_REMOVED)->firstOrFail();

    expect($agregado->entity)->toBe('Proyecto P-AUD')
        ->and($agregado->new_values)->toBe(['integrante' => 'integrante@dima.cl'])
        ->and($retirado->old_values)->toBe(['integrante' => 'integrante@dima.cl'])
        ->and($retirado->user_id)->toBe($admin->id);
});

test('seeding the database does not write automatic audit entries', function () {
    $this->seed(DatabaseSeeder::class);

    expect(AuditLog::whereNull('user_id')->count())->toBe(0);
});

test('only the administrador can open the audit log', function (string $estado, int $esperado) {
    $this->actingAs(User::factory()->{$estado}()->create());

    $this->get(route('bitacora.index'))->assertStatus($esperado);
})->with([
    'administrador' => ['administrador', 200],
    'gestor' => ['gestorProcesos', 403],
    'jefe' => ['jefeProyecto', 403],
    'colaborador' => ['colaborador', 403],
]);

test('a guest is redirected to login from the audit log', function () {
    $this->get(route('bitacora.index'))->assertRedirect(route('login'));
});

test('the audit log lists who changed what and when', function () {
    $admin = User::factory()->administrador()->create(['name' => 'Ana Admin']);
    $this->actingAs($admin);

    $proyecto = proyectoAuditado();
    $proyecto->update(['name' => 'Proyecto Renombrado']);

    Livewire::test(AuditLogList::class)
        ->assertSee('Ana Admin')
        ->assertSee('Proyecto P-AUD')
        ->assertSee(AuditLog::ACTION_CREATED)
        ->assertSee(AuditLog::ACTION_UPDATED)
        ->call('toggle', AuditLog::where('action', AuditLog::ACTION_UPDATED)->value('id'))
        ->assertSeeInOrder(['name', 'Proyecto Auditado', 'Proyecto Renombrado']);
});

test('the audit log can be filtered', function (string $propiedad, string $valor, string $visible, string $oculto) {
    $this->actingAs(User::factory()->administrador()->create());

    proyectoAuditado()->update(['name' => 'Proyecto Renombrado']);
    User::factory()->colaborador()->create(['email' => 'filtro@dima.cl']);

    Livewire::test(AuditLogList::class)
        ->set($propiedad, $valor === 'mañana' ? now()->addDay()->toDateString() : $valor)
        ->assertViewHas('logs', function ($logs) use ($visible, $oculto): bool {
            $entidades = collect($logs->items())->pluck('entity');

            return ($visible === '' || $entidades->contains($visible)) && ! $entidades->contains($oculto);
        });
})->with([
    'entidad' => ['entityFilter', User::class, 'Usuario filtro@dima.cl', 'Proyecto P-AUD'],
    'acción' => ['actionFilter', AuditLog::ACTION_UPDATED, 'Proyecto P-AUD', 'Usuario filtro@dima.cl'],
    'búsqueda' => ['search', 'P-AUD', 'Proyecto P-AUD', 'Usuario filtro@dima.cl'],
    'fecha futura' => ['dateFrom', 'mañana', '', 'Proyecto P-AUD'],
]);

test('the menu shows the audit log only to the administrador', function () {
    $this->actingAs(User::factory()->administrador()->create());
    $this->get(route('dashboard'))->assertSee(route('bitacora.index'), false);

    app('auth')->forgetGuards();

    $this->actingAs(User::factory()->gestorProcesos()->create());
    $this->get(route('dashboard'))->assertDontSee(route('bitacora.index'), false);
});
