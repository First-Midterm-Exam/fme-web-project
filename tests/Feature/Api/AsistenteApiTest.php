<?php

use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CmmiCatalogSeeder;
use Database\Seeders\EvidenceStatusSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as PeticionHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(CmmiCatalogSeeder::class);
    $this->seed(EvidenceStatusSeeder::class);
    Storage::fake('local');
    config(['services.groq.key' => '']);
});

/**
 * @return array{0: Appraisal, 1: Project, 2: User}
 */
function escenarioAsistente(): array
{
    $gestor = User::factory()->gestorProcesos()->create();

    $proyecto = Project::create(['name' => 'Sistema de Facturación', 'code' => 'P-FAC', 'start_date' => '2026-01-01', 'status' => 'activo']);

    $appraisal = Appraisal::create([
        'project_id' => $proyecto->id,
        'name' => 'Appraisal ML2 2026',
        'domain' => 'Development',
        'target_level' => 2,
        'target_date' => '2026-11-30',
        'status' => 'activo',
    ]);

    $practicas = Practice::whereIn('code', ['PLAN 1.1', 'EST 1.1'])->get();
    $appraisal->practices()->sync(AppraisalScope::pivotFor($practicas->pluck('id')));

    $plan = $practicas->firstWhere('code', 'PLAN 1.1');
    $evaluacion = PracticeEvaluation::create([
        'appraisal_id' => $appraisal->id,
        'practice_id' => $plan->id,
        'status' => PracticeEvaluation::STATUS_PARCIAL,
    ]);

    [$primero, $segundo] = $plan->criteria->values()->all();

    CriterionCheck::create([
        'practice_evaluation_id' => $evaluacion->id,
        'practice_criterion_id' => $primero->id,
        'status' => CriterionCheck::STATUS_CUMPLE,
    ]);

    CriterionCheck::create([
        'practice_evaluation_id' => $evaluacion->id,
        'practice_criterion_id' => $segundo->id,
        'status' => CriterionCheck::STATUS_NO_CUMPLE,
        'notes' => 'Roles sin asignar en el acta.',
    ]);

    Evidence::create([
        'code' => 'EV-0001',
        'project_id' => $proyecto->id,
        'name' => 'Plan de proyecto v1',
        'type' => Evidence::TYPE_PLAN,
        'status_id' => EvidenceStatus::REGISTRADA,
        'uploaded_by' => $gestor->id,
    ]);

    return [$appraisal, $proyecto, $gestor];
}

/**
 * @param  array<string, mixed>  $clasificacion
 * @param  array<string, mixed>  $redaccion
 */
function simularGroq(array $clasificacion, array $redaccion): void
{
    config(['services.groq.key' => 'clave-de-prueba']);

    Http::fake([
        'api.groq.com/*' => function (PeticionHttp $peticion) use ($clasificacion, $redaccion) {
            $sistema = (string) ($peticion['messages'][0]['content'] ?? '');
            $contenido = str_contains($sistema, 'Clasificas') ? $clasificacion : $redaccion;

            return Http::response(['choices' => [['message' => ['content' => json_encode($contenido)]]]]);
        },
    ]);
}

function consultar(Appraisal $appraisal, string $pregunta): TestResponse
{
    return test()->postJson('/api/asistente/consultas', [
        'appraisal_id' => $appraisal->id,
        'pregunta' => $pregunta,
    ]);
}

test('the assistant answers with the fields the app expects', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $respuesta = consultar($appraisal, '¿Cómo vamos con el appraisal?')->assertOk();

    expect($respuesta->json('resumen_voz'))->toBeString()->not->toBeEmpty()
        ->and($respuesta->json('reporte_markdown'))->toContain('| Indicador | Valor |')
        ->and($respuesta->json('tipo_reporte'))->toBe('resumen_appraisal')
        ->and($respuesta->json('generado_en'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/')
        ->and($respuesta->json('archivo'))->toBeNull();
});

test('the retrieved context comes from the real appraisal data', function (string $pregunta, string $tipo, array $esperados, array $ausentes) {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $markdown = consultar($appraisal, $pregunta)
        ->assertOk()
        ->assertJsonPath('tipo_reporte', $tipo)
        ->json('reporte_markdown');

    foreach ($esperados as $texto) {
        expect($markdown)->toContain($texto);
    }

    foreach ($ausentes as $texto) {
        expect($markdown)->not->toContain($texto);
    }
})->with([
    'cumplimiento' => ['Muéstrame el cumplimiento por práctica', 'cumplimiento_practicas', ['PLAN 1.1', 'EST 1.1', '50%'], []],
    'criterios' => ['¿Qué criterios no se cumplen?', 'criterios_no_cumplidos', ['PLAN 1.1-C2', 'Roles sin asignar en el acta.'], ['PLAN 1.1-C1']],
    'evidencias' => ['Lista las evidencias del proyecto', 'evidencias', ['EV-0001', 'Plan de proyecto v1'], []],
    'gaps' => ['Dame los gaps críticos', 'gaps_criticos', ['GAP-', 'datos de ejemplo'], ['Baja']],
]);

test('a report request generates a pdf that can be downloaded', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $archivo = consultar($appraisal, 'Genera el reporte de gaps críticos en PDF')
        ->assertOk()
        ->assertJsonPath('archivo.formato', 'pdf')
        ->json('archivo');

    expect($archivo['id'])->toStartWith('rep-')
        ->and($archivo['nombre'])->toBe('reporte-gaps-criticos-'.now()->format('Y-m-d').'.pdf')
        ->and($archivo['url'])->toStartWith('http')
        ->and($archivo['tamano_bytes'])->toBeGreaterThan(0)
        ->and($archivo['expira_en'])->toMatch('/Z$/');

    $descarga = $this->get($archivo['url'], ['Accept' => '*/*'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload($archivo['nombre']);

    expect($descarga->streamedContent())->toStartWith('%PDF');
});

test('an excel request generates an xlsx file', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $archivo = consultar($appraisal, 'Exporta el cumplimiento por práctica a Excel')
        ->assertOk()
        ->assertJsonPath('archivo.formato', 'xlsx')
        ->json('archivo');

    $descarga = $this->get($archivo['url'], ['Accept' => '*/*'])
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    expect($descarga->streamedContent())->toStartWith('PK');
});

test('the download link does not require the bearer token', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $url = consultar($appraisal, 'Genera el reporte de evidencias')->json('archivo.url');

    app('auth')->forgetGuards();

    $this->get($url, ['Accept' => '*/*'])->assertOk();
});

test('an expired download link answers 410', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $url = consultar($appraisal, 'Genera el reporte de evidencias')->json('archivo.url');

    $this->travel(25)->hours();

    $this->get($url, ['Accept' => '*/*'])->assertStatus(410);
});

test('a tampered download link answers 404', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $url = consultar($appraisal, 'Genera el reporte de evidencias')->json('archivo.url');

    $this->get(str_replace('formato=pdf', 'formato=xlsx', $url), ['Accept' => '*/*'])->assertNotFound();
});

test('a removed file answers 404 even with a valid link', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $archivo = consultar($appraisal, 'Genera el reporte de evidencias')->json('archivo');

    Storage::disk('local')->delete('asistente/'.$archivo['id'].'.pdf');

    $this->get($archivo['url'], ['Accept' => '*/*'])->assertNotFound();
});

test('with groq configured the llm classifies and writes over the retrieved data', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    simularGroq(
        ['tipo_reporte' => 'criterios_no_cumplidos', 'formato' => null],
        ['resumen_voz' => 'Hay un criterio sin cumplir en PLAN 1.1.', 'analisis_markdown' => '- El criterio **PLAN 1.1-C2** no se cumple.'],
    );

    consultar($appraisal, '¿Qué nos falta para aprobar planificación?')
        ->assertOk()
        ->assertJsonPath('tipo_reporte', 'criterios_no_cumplidos')
        ->assertJsonPath('resumen_voz', 'Hay un criterio sin cumplir en PLAN 1.1.')
        ->assertJsonPath('archivo', null);

    Http::assertSentCount(2);
    Http::assertSent(fn (PeticionHttp $peticion): bool => $peticion->hasHeader('Authorization', 'Bearer clave-de-prueba')
        && str_contains((string) ($peticion['messages'][1]['content'] ?? ''), 'Roles sin asignar en el acta.'));
});

test('an explicit format in the question wins over the llm decision', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    simularGroq(
        ['tipo_reporte' => 'evidencias', 'formato' => null],
        ['resumen_voz' => 'Listo.', 'analisis_markdown' => '- Una evidencia registrada.'],
    );

    consultar($appraisal, 'Quiero las evidencias en PDF')
        ->assertOk()
        ->assertJsonPath('archivo.formato', 'pdf');
});

test('an unknown report type from the llm falls back to the keyword heuristic', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    simularGroq(
        ['tipo_reporte' => 'inventado', 'formato' => 'docx'],
        ['resumen_voz' => '', 'analisis_markdown' => ''],
    );

    $respuesta = consultar($appraisal, 'Lista las evidencias del proyecto')
        ->assertOk()
        ->assertJsonPath('tipo_reporte', 'evidencias')
        ->assertJsonPath('archivo', null);

    expect($respuesta->json('resumen_voz'))->not->toBeEmpty();
});

test('an unavailable llm answers 503', function (string $falla) {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    config(['services.groq.key' => 'clave-de-prueba']);
    Http::fake(['api.groq.com/*' => $falla === 'servidor'
        ? Http::response(['error' => 'caído'], 500)
        : fn () => throw new ConnectionException('timeout'),
    ]);

    consultar($appraisal, 'Resumen del appraisal')->assertStatus(503);
})->with([
    'error del servidor' => ['servidor'],
    'sin conexión' => ['conexion'],
]);

test('an llm rate limit answers 429 with retry after', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    config(['services.groq.key' => 'clave-de-prueba']);
    Http::fake(['api.groq.com/*' => Http::response(['error' => 'rate'], 429, ['Retry-After' => '12'])]);

    consultar($appraisal, 'Resumen del appraisal')
        ->assertStatus(429)
        ->assertHeader('Retry-After', '12');
});

test('the assistant is throttled per user with retry after', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    foreach (range(1, 10) as $intento) {
        consultar($appraisal, 'Resumen del appraisal')->assertOk();
    }

    consultar($appraisal, 'Resumen del appraisal')
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

test('a user cannot query an appraisal outside their projects', function () {
    [$appraisal] = escenarioAsistente();
    Sanctum::actingAs(User::factory()->colaborador()->create());

    consultar($appraisal, 'Resumen del appraisal')->assertForbidden();
});

test('an assigned jefe de proyecto can query the appraisal of their project', function () {
    [$appraisal, $proyecto] = escenarioAsistente();
    $jefe = User::factory()->jefeProyecto()->create();
    $proyecto->users()->attach($jefe->id);

    Sanctum::actingAs($jefe);

    consultar($appraisal, 'Resumen del appraisal')->assertOk();
});

test('an empty question or an unknown appraisal answers 422', function (array $datos) {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    $datos = array_map(fn ($valor) => $valor === 'propio' ? $appraisal->id : $valor, $datos);

    $this->postJson('/api/asistente/consultas', $datos)->assertStatus(422);
})->with([
    'pregunta vacía' => [['appraisal_id' => 'propio', 'pregunta' => '   ']],
    'sin pregunta' => [['appraisal_id' => 'propio']],
    'appraisal inexistente' => [['appraisal_id' => 9999, 'pregunta' => 'Resumen']],
]);

test('the llm is told which file the platform generated', function () {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    simularGroq(
        ['tipo_reporte' => 'gaps_criticos', 'formato' => 'pdf'],
        ['resumen_voz' => 'Listo, el reporte en PDF está disponible.', 'analisis_markdown' => '- Tres gaps críticos.'],
    );

    consultar($appraisal, 'Genera el reporte de gaps críticos en PDF')
        ->assertOk()
        ->assertJsonPath('archivo.formato', 'pdf');

    Http::assertSent(fn (PeticionHttp $peticion): bool => str_contains((string) ($peticion['messages'][1]['content'] ?? ''), '"archivo_generado": "PDF"')
        && str_contains((string) ($peticion['messages'][0]['content'] ?? ''), 'Nunca digas que no puedes generar'));
});

test('the fallback voice summary confirms the generated file', function (string $pregunta, string $formato) {
    [$appraisal, , $gestor] = escenarioAsistente();
    Sanctum::actingAs($gestor);

    expect(consultar($appraisal, $pregunta)->assertOk()->json('resumen_voz'))
        ->toContain('El reporte en '.$formato.' está listo para descargar.');
})->with([
    'pdf' => ['Genera el reporte de gaps críticos en PDF', 'PDF'],
    'excel' => ['Exporta las evidencias a Excel', 'Excel'],
]);
