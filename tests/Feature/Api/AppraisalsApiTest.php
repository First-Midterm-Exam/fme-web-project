<?php

use App\Models\Appraisal;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * @return array{0: Project, 1: Project}
 */
function crearProyectosConAppraisals(): array
{
    $propio = Project::create(['name' => 'Sistema de Facturación', 'code' => 'P-FAC', 'start_date' => '2026-01-01', 'status' => 'activo']);
    $ajeno = Project::create(['name' => 'Portal de Clientes', 'code' => 'P-POR', 'start_date' => '2026-01-01', 'status' => 'activo']);

    Appraisal::create(['project_id' => $propio->id, 'name' => 'Appraisal ML2 2026', 'domain' => 'Development', 'target_level' => 2, 'target_date' => '2026-11-30', 'status' => 'activo']);
    Appraisal::create(['project_id' => $propio->id, 'name' => 'Appraisal borrador', 'domain' => 'Development', 'target_level' => 3, 'target_date' => '2027-03-31', 'status' => 'borrador']);
    Appraisal::create(['project_id' => $ajeno->id, 'name' => 'Appraisal Portal', 'domain' => 'Services', 'target_level' => 2, 'target_date' => '2026-12-15', 'status' => 'activo']);

    return [$propio, $ajeno];
}

test('appraisals are returned inside data with the fields the app expects', function () {
    crearProyectosConAppraisals();
    Sanctum::actingAs(User::factory()->gestorProcesos()->create());

    $this->getJson('/api/appraisals?estado=activo')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'nombre', 'proyecto', 'nivel_objetivo', 'fecha_meta']]])
        ->assertJsonFragment([
            'nombre' => 'Appraisal ML2 2026',
            'proyecto' => 'Sistema de Facturación',
            'nivel_objetivo' => 2,
            'fecha_meta' => '2026-11-30',
        ]);
});

test('without estado filter every visible appraisal is returned', function () {
    crearProyectosConAppraisals();
    Sanctum::actingAs(User::factory()->administrador()->create());

    $this->getJson('/api/appraisals')->assertOk()->assertJsonCount(3, 'data');
});

test('a jefe de proyecto only receives appraisals of assigned projects', function () {
    [$propio] = crearProyectosConAppraisals();
    $jefe = User::factory()->jefeProyecto()->create();
    $propio->users()->attach($jefe->id);

    Sanctum::actingAs($jefe);

    $this->getJson('/api/appraisals?estado=activo')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Appraisal ML2 2026');
});

test('a colaborador without assigned projects receives an empty list', function () {
    crearProyectosConAppraisals();
    Sanctum::actingAs(User::factory()->colaborador()->create());

    $this->getJson('/api/appraisals')->assertOk()->assertExactJson(['data' => []]);
});

test('an unknown estado value is rejected with 422', function () {
    Sanctum::actingAs(User::factory()->gestorProcesos()->create());

    $this->getJson('/api/appraisals?estado=archivado')->assertStatus(422);
});
