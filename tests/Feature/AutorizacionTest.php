<?php

use App\Livewire\Users\UserManagement;
use App\Models\Evidence;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;
use App\Policies\EvidencePolicy;
use App\Policies\UserPolicy;
use App\Support\Capacidades;
use Database\Seeders\EvidenceStatusSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

test('un administrador accede a la gestion de usuarios', function () {
    $this->actingAs(User::factory()->administrador()->create());

    $this->get('/users')->assertOk();
});

test('los demas roles reciben 403 al entrar por url directa a la gestion de usuarios', function (string $estado) {
    $this->actingAs(User::factory()->{$estado}()->create());

    $this->get('/users')->assertForbidden();
})->with(['gestorProcesos', 'jefeProyecto', 'colaborador']);

test('un invitado es redirigido al login y no recibe 403', function () {
    $respuesta = $this->get('/users');

    $respuesta->assertRedirect(route('login'));
    expect($respuesta->getStatusCode())->not->toBe(403);
});

test('el gate verificar-evidencias solo autoriza a gestor de procesos y administrador', function (string $estado, bool $esperado) {
    $usuario = User::factory()->{$estado}()->create();

    expect(Gate::forUser($usuario)->allows(Capacidades::VERIFICAR_EVIDENCIAS))->toBe($esperado);
})->with([
    ['administrador', true],
    ['gestorProcesos', true],
    ['jefeProyecto', false],
    ['colaborador', false],
]);

test('cada capacidad del sistema solo autoriza a los roles de su mapa', function (string $capacidad, array $rolIds) {
    foreach (Rol::ETIQUETAS as $rolId => $etiqueta) {
        $usuario = User::factory()->conRol($rolId)->create();

        expect(Gate::forUser($usuario)->allows($capacidad))->toBe(in_array($rolId, $rolIds, true));
    }
})->with(array_map(
    fn (string $capacidad): array => [$capacidad, Capacidades::rolesDe($capacidad)],
    Capacidades::todas()
));

test('cada modulo restringe el acceso por url directa segun el rol', function (string $ruta, array $rolIds) {
    foreach (Rol::ETIQUETAS as $rolId => $etiqueta) {
        $this->actingAs(User::factory()->conRol($rolId)->create());

        $respuesta = $this->get(route($ruta));

        in_array($rolId, $rolIds, true)
            ? $respuesta->assertOk()
            : $respuesta->assertForbidden();
    }
})->with([
    ['proyectos.index', [Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO, Rol::COLABORADOR]],
    ['appraisals.index', [Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO, Rol::COLABORADOR]],
    ['alcance.areas', [Rol::GESTOR_PROCESOS]],
    ['alcance.practicas', [Rol::GESTOR_PROCESOS]],
    ['cumplimiento.evaluacion', [Rol::GESTOR_PROCESOS]],
    ['cumplimiento.criterios', [Rol::GESTOR_PROCESOS]],
    ['evidencias.index', [Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO, Rol::COLABORADOR]],
    ['evidencias.verificacion', [Rol::GESTOR_PROCESOS, Rol::ADMINISTRADOR]],
    ['gaps.index', [Rol::GESTOR_PROCESOS]],
    ['gaps.acciones', [Rol::GESTOR_PROCESOS]],
    ['readiness.index', [Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO]],
    ['readiness.trazabilidad', [Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO]],
]);

test('la policy de usuarios autoriza viewAny solo al administrador', function () {
    $policy = new UserPolicy;

    expect($policy->viewAny(User::factory()->administrador()->create()))->toBeTrue()
        ->and($policy->viewAny(User::factory()->gestorProcesos()->create()))->toBeFalse()
        ->and($policy->viewAny(User::factory()->jefeProyecto()->create()))->toBeFalse()
        ->and($policy->viewAny(User::factory()->colaborador()->create()))->toBeFalse();
});

test('la policy de usuarios autoriza view solo al administrador', function () {
    $policy = new UserPolicy;
    $objetivo = User::factory()->colaborador()->create();

    expect($policy->view(User::factory()->administrador()->create(), $objetivo))->toBeTrue()
        ->and($policy->view(User::factory()->gestorProcesos()->create(), $objetivo))->toBeFalse();
});

test('la policy de usuarios autoriza create solo al administrador', function () {
    $policy = new UserPolicy;

    expect($policy->create(User::factory()->administrador()->create()))->toBeTrue()
        ->and($policy->create(User::factory()->jefeProyecto()->create()))->toBeFalse();
});

test('la policy de usuarios autoriza update solo al administrador', function () {
    $policy = new UserPolicy;
    $objetivo = User::factory()->colaborador()->create();

    expect($policy->update(User::factory()->administrador()->create(), $objetivo))->toBeTrue()
        ->and($policy->update(User::factory()->colaborador()->create(), $objetivo))->toBeFalse();
});

test('la policy de usuarios autoriza delete solo al administrador sobre otro usuario', function () {
    $policy = new UserPolicy;
    $administrador = User::factory()->administrador()->create();
    $objetivo = User::factory()->colaborador()->create();

    expect($policy->delete($administrador, $objetivo))->toBeTrue()
        ->and($policy->delete($administrador, $administrador))->toBeFalse()
        ->and($policy->delete(User::factory()->gestorProcesos()->create(), $objetivo))->toBeFalse();
});

test('un administrador no puede desactivarse a si mismo', function () {
    $administrador = User::factory()->administrador()->create(['is_active' => true]);

    $this->actingAs($administrador);

    Livewire::test(UserManagement::class)
        ->call('toggleStatus', $administrador->id)
        ->assertForbidden();

    expect($administrador->fresh()->is_active)->toBeTrue();
});

test('el menu no renderiza la opcion de usuarios para un colaborador', function () {
    $this->actingAs(User::factory()->colaborador()->create());

    $respuesta = $this->get(route('dashboard'));

    $respuesta->assertOk()
        ->assertDontSee(route('users.index'))
        ->assertSee(route('proyectos.index'))
        ->assertSee(route('evidencias.index'));
});

test('el menu si renderiza la opcion de usuarios para un administrador', function () {
    $this->actingAs(User::factory()->administrador()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('users.index'));
});

test('el menu lateral solo muestra los grupos permitidos por el rol', function (string $estado, array $visibles, array $ocultos) {
    $this->actingAs(User::factory()->{$estado}()->create());

    $respuesta = $this->get(route('dashboard'))->assertOk();

    foreach ($visibles as $grupo) {
        $respuesta->assertSee($grupo);
    }

    foreach ($ocultos as $grupo) {
        $respuesta->assertDontSee($grupo);
    }
})->with([
    ['administrador', ['Usuarios', 'Proyectos', 'Appraisals'], ['Alcance CMMI', 'Gaps y Acciones', 'Reportes']],
    ['gestorProcesos', ['Alcance CMMI', 'Cumplimiento', 'Gaps y Acciones', 'Reportes', 'Proyectos', 'Appraisals'], ['Usuarios']],
    ['jefeProyecto', ['Reportes', 'Trazabilidad', 'Proyectos', 'Appraisals'], ['Usuarios', 'Cumplimiento', 'Gaps y Acciones']],
    ['colaborador', ['Registro de Evidencias', 'Proyectos', 'Appraisals'], ['Usuarios', 'Reportes', 'Gaps y Acciones']],
]);

test('asignar un rol inexistente a un usuario falla', function () {
    $usuario = User::factory()->colaborador()->create();

    expect(fn () => $usuario->assignRole('Rol Inexistente'))->toThrow(RoleDoesNotExist::class);
});

test('un usuario no puede quedar asociado a un rol inexistente por integridad referencial', function () {
    $usuario = User::factory()->colaborador()->create();

    expect(fn () => DB::table('model_has_roles')->insert([
        'role_id' => 999,
        'model_type' => User::class,
        'model_id' => $usuario->id,
    ]))->toThrow(QueryException::class);
});

test('el middleware de rol rechaza con 403 y nunca redirige', function () {
    $this->actingAs(User::factory()->colaborador()->create());

    $respuesta = $this->get('/users');

    $respuesta->assertForbidden();
    expect($respuesta->isRedirect())->toBeFalse();
});

test('la pagina 403 se muestra en espanol', function () {
    $this->actingAs(User::factory()->colaborador()->create());

    $this->get('/users')
        ->assertForbidden()
        ->assertSee('Acceso denegado')
        ->assertSee('Volver al inicio');
});

test('un jefe de proyecto solo ve evidencias de sus proyectos asignados', function () {
    $this->seed(EvidenceStatusSeeder::class);
    $jefe = User::factory()->jefeProyecto()->create();
    $proyectoAsignado = Project::create([
        'name' => 'Proyecto Asignado',
        'code' => 'ASIG-01',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $proyectoAjeno = Project::create([
        'name' => 'Proyecto Ajeno',
        'code' => 'AJEN-01',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);
    $proyectoAsignado->users()->attach($jefe->id);

    $evidenciaAsignada = Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $proyectoAsignado->id,
        'name' => 'Evidencia 1',
        'type' => Evidence::TYPE_ACTA,
        'status_id' => 1,
        'uploaded_by' => $jefe->id,
    ]);

    $evidenciaAjena = Evidence::create([
        'code' => 'EV-0002',
        'project_id' => $proyectoAjeno->id,
        'name' => 'Evidencia 2',
        'type' => Evidence::TYPE_INFORME,
        'status_id' => 1,
        'uploaded_by' => $jefe->id,
    ]);

    $policy = new EvidencePolicy;

    expect($policy->view($jefe, $evidenciaAsignada))->toBeTrue()
        ->and($policy->view($jefe, $evidenciaAjena))->toBeFalse();
});

test('el detalle de un proyecto permite acceso a administrador y gestor de procesos, y restringe a jefe de proyecto y colaborador si no estan asignados', function () {
    $proyecto = Project::create([
        'name' => 'Proyecto Demo',
        'code' => 'DEMO-01',
        'start_date' => '2026-01-01',
        'status' => 'activo',
    ]);

    $admin = User::factory()->administrador()->create();
    $gestor = User::factory()->gestorProcesos()->create();
    $jefeNoAsignado = User::factory()->jefeProyecto()->create();
    $colaboradorNoAsignado = User::factory()->colaborador()->create();

    $jefeAsignado = User::factory()->jefeProyecto()->create();
    $colaboradorAsignado = User::factory()->colaborador()->create();
    $proyecto->users()->attach([$jefeAsignado->id, $colaboradorAsignado->id]);

    $this->actingAs($admin)->get(route('proyectos.show', $proyecto))->assertOk();
    $this->actingAs($gestor)->get(route('proyectos.show', $proyecto))->assertOk();
    $this->actingAs($jefeAsignado)->get(route('proyectos.show', $proyecto))->assertOk();
    $this->actingAs($colaboradorAsignado)->get(route('proyectos.show', $proyecto))->assertOk();

    $this->actingAs($jefeNoAsignado)->get(route('proyectos.show', $proyecto))->assertForbidden();
    $this->actingAs($colaboradorNoAsignado)->get(route('proyectos.show', $proyecto))->assertForbidden();
});
