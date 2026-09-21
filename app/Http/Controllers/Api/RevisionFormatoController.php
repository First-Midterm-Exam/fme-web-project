<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RevisionFormatoRequest;
use App\Services\Documentos\RevisorFormato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class RevisionFormatoController extends Controller
{
    private const SEVERIDADES = ['alta', 'media', 'baja'];

    public function __invoke(RevisionFormatoRequest $request, RevisorFormato $revisor): JsonResponse
    {
        /** @var UploadedFile $imagen */
        $imagen = $request->file('imagen');

        return response()->json($this->normalizar($revisor->revisar($imagen)));
    }

    /**
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function normalizar(array $resultado): array
    {
        $hallazgos = collect(is_array($resultado['hallazgos'] ?? null) ? $resultado['hallazgos'] : [])
            ->filter(fn ($hallazgo): bool => is_array($hallazgo))
            ->map(fn (array $hallazgo): array => [
                'severidad' => in_array($hallazgo['severidad'] ?? null, self::SEVERIDADES, true)
                    ? $hallazgo['severidad']
                    : 'baja',
                'elemento' => (string) ($hallazgo['elemento'] ?? ''),
                'mensaje' => (string) ($hallazgo['mensaje'] ?? ''),
            ])
            ->values()
            ->all();

        return [
            'cumple' => filter_var($resultado['cumple'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'puntaje' => max(0, min(100, (int) ($resultado['puntaje'] ?? 0))),
            'tipo_detectado' => (string) ($resultado['tipo_detectado'] ?? ''),
            'resumen' => (string) ($resultado['resumen'] ?? ''),
            'hallazgos' => $hallazgos,
        ];
    }
}
