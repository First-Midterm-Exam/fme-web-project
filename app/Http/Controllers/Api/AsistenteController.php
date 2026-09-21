<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConsultaAsistenteRequest;
use App\Models\Appraisal;
use App\Services\Asistente\AsistenteService;
use App\Services\Asistente\GeneradorArchivos;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsistenteController extends Controller
{
    use AuthorizesRequests;

    public function consultar(ConsultaAsistenteRequest $request, AsistenteService $asistente): JsonResponse
    {
        $appraisal = Appraisal::with('project')->findOrFail($request->integer('appraisal_id'));

        $this->authorize('view', $appraisal);

        return response()->json(
            $asistente->responder($request->string('pregunta')->toString(), $appraisal)
        );
    }

    public function descargar(Request $request, GeneradorArchivos $generador, string $archivo): StreamedResponse
    {
        abort_unless(URL::hasCorrectSignature($request), 404);
        abort_unless(URL::signatureHasNotExpired($request), 410);

        $formato = $request->string('formato')->toString();
        abort_unless(array_key_exists($formato, GeneradorArchivos::TIPOS_MIME), 404);

        $ruta = $generador->rutaDe($archivo, $formato);
        $disco = $generador->disco();
        abort_unless($disco->exists($ruta), 404);

        return $disco->download(
            $ruta,
            $request->string('nombre', basename($ruta))->toString(),
            ['Content-Type' => GeneradorArchivos::TIPOS_MIME[$formato]]
        );
    }
}
