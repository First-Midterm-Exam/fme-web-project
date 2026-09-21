<?php

use App\Models\User;
use App\Services\Documentos\RevisorFormato;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

function archivoSubidoReal(string $nombre, string $contenido): UploadedFile
{
    $ruta = tempnam(sys_get_temp_dir(), 'api');
    file_put_contents($ruta, $contenido);

    return new UploadedFile($ruta, $nombre, null, null, true);
}

function imagenJpegDePrueba(): UploadedFile
{
    return archivoSubidoReal('documento.jpg', base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));
}

test('any authenticated role receives the format review', function (string $estado) {
    Sanctum::actingAs(User::factory()->{$estado}()->create());

    $this->post('/api/documentos/revision-formato', ['imagen' => imagenJpegDePrueba()], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonStructure(['cumple', 'puntaje', 'tipo_detectado', 'resumen', 'hallazgos' => [['severidad', 'elemento', 'mensaje']]])
        ->assertJsonPath('cumple', false)
        ->assertJsonPath('puntaje', 72);
})->with(['administrador', 'gestorProcesos', 'jefeProyecto', 'colaborador']);

test('an image larger than 5 MB is rejected with 413', function () {
    Sanctum::actingAs(User::factory()->colaborador()->create());

    $grande = UploadedFile::fake()->create('documento.jpg', 5121, 'image/jpeg');

    $this->post('/api/documentos/revision-formato', ['imagen' => $grande], ['Accept' => 'application/json'])
        ->assertStatus(413);
});

test('a missing or non jpeg file is rejected with 422', function (?UploadedFile $archivo) {
    Sanctum::actingAs(User::factory()->colaborador()->create());

    $datos = $archivo === null ? [] : ['imagen' => $archivo];

    $this->post('/api/documentos/revision-formato', $datos, ['Accept' => 'application/json'])
        ->assertStatus(422);
})->with([
    'sin imagen' => [null],
    'texto con extension jpg' => [fn () => archivoSubidoReal('documento.jpg', 'no es una imagen')],
    'pdf' => [fn () => archivoSubidoReal('documento.pdf', "%PDF-1.4\n%%EOF")],
]);

test('the review result is normalized before reaching the app', function () {
    app()->bind(RevisorFormato::class, fn () => new class implements RevisorFormato
    {
        public function revisar(UploadedFile $imagen): array
        {
            return [
                'cumple' => 'true',
                'puntaje' => 140,
                'tipo_detectado' => 'Acta de reunión',
                'resumen' => 'Documento completo.',
                'hallazgos' => [
                    ['severidad' => 'crítica', 'elemento' => 'Firmas', 'mensaje' => 'Faltan firmas.'],
                    'hallazgo mal formado',
                ],
            ];
        }
    });

    Sanctum::actingAs(User::factory()->colaborador()->create());

    $this->post('/api/documentos/revision-formato', ['imagen' => imagenJpegDePrueba()], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('cumple', true)
        ->assertJsonPath('puntaje', 100)
        ->assertJsonCount(1, 'hallazgos')
        ->assertJsonPath('hallazgos.0.severidad', 'baja');
});
