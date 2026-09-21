<?php

namespace App\Services\Asistente;

use App\Models\Appraisal;

class AsistenteService
{
    public function __construct(
        private readonly ClasificadorConsulta $clasificador,
        private readonly RecuperadorContexto $recuperador,
        private readonly RedactorRespuesta $redactor,
        private readonly GeneradorArchivos $generador,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function responder(string $pregunta, Appraisal $appraisal): array
    {
        $intencion = $this->clasificador->clasificar($pregunta);
        $contexto = $this->recuperador->recuperar($intencion['tipo'], $appraisal);
        $redaccion = $this->redactor->redactar($pregunta, $appraisal, $contexto, $intencion['formato']);
        $markdown = $this->componerMarkdown($contexto, $redaccion['analisis']);

        return [
            'resumen_voz' => $redaccion['resumen_voz'],
            'reporte_markdown' => $markdown,
            'tipo_reporte' => $contexto->tipo,
            'generado_en' => now()->utc()->toIso8601ZuluString(),
            'archivo' => $intencion['formato'] === null
                ? null
                : $this->generador->generar($intencion['formato'], $appraisal, $contexto, $markdown),
        ];
    }

    private function componerMarkdown(ContextoReporte $contexto, string $analisis): string
    {
        $secciones = ['## '.$contexto->titulo];

        if ($contexto->nota !== null) {
            $secciones[] = '> '.$contexto->nota;
        }

        if ($analisis !== '') {
            $secciones[] = $analisis;
        }

        $secciones[] = '### Detalle';
        $secciones[] = $contexto->tablaMarkdown();

        return implode("\n\n", $secciones);
    }
}
