<?php

namespace App\Services\Asistente;

use App\Models\Appraisal;
use App\Services\Ia\ClienteGroq;
use Illuminate\Support\Str;

class RedactorRespuesta
{
    private const MAXIMO_FILAS_CONTEXTO = 80;

    public function __construct(private readonly ClienteGroq $groq) {}

    /**
     * @return array{resumen_voz: string, analisis: string}
     */
    public function redactar(string $pregunta, Appraisal $appraisal, ContextoReporte $contexto, ?string $formato = null): array
    {
        $respaldo = $this->respaldo($appraisal, $contexto, $formato);

        if (! $this->groq->configurado()) {
            return $respaldo;
        }

        $respuesta = $this->groq->json([
            ['role' => 'system', 'content' => $this->instrucciones()],
            ['role' => 'user', 'content' => $this->mensaje($pregunta, $appraisal, $contexto, $formato)],
        ]);

        $voz = is_string($respuesta['resumen_voz'] ?? null) ? trim($respuesta['resumen_voz']) : '';
        $analisis = is_string($respuesta['analisis_markdown'] ?? null) ? trim($respuesta['analisis_markdown']) : '';

        return [
            'resumen_voz' => $voz !== '' ? $voz : $respaldo['resumen_voz'],
            'analisis' => $analisis !== '' ? $analisis : $respaldo['analisis'],
        ];
    }

    /**
     * @return array{resumen_voz: string, analisis: string}
     */
    private function respaldo(Appraisal $appraisal, ContextoReporte $contexto, ?string $formato): array
    {
        $registros = count($contexto->filas);

        $voz = sprintf(
            'Listo. Revisé %s del appraisal %s y encontré %d %s.',
            Str::lower($contexto->titulo),
            $appraisal->name,
            $registros,
            $registros === 1 ? 'registro' : 'registros'
        );

        if ($formato !== null) {
            $voz .= ' El reporte en '.$this->nombreFormato($formato).' está listo para descargar.';
        }

        return [
            'resumen_voz' => $voz,
            'analisis' => collect($contexto->hechos)
                ->map(fn (string|int|float|null $valor, string $clave): string => '- **'.$clave.':** '.($valor ?? '—'))
                ->implode("\n"),
        ];
    }

    private function mensaje(string $pregunta, Appraisal $appraisal, ContextoReporte $contexto, ?string $formato): string
    {
        $datos = [
            'appraisal' => $appraisal->name,
            'proyecto' => $appraisal->project->name,
            'reporte' => $contexto->titulo,
            'archivo_generado' => $formato === null ? null : $this->nombreFormato($formato),
            'hechos' => $contexto->hechos,
            'columnas' => $contexto->columnas,
            'filas' => array_slice($contexto->filas, 0, self::MAXIMO_FILAS_CONTEXTO),
            'filas_totales' => count($contexto->filas),
            'nota' => $contexto->nota,
        ];

        return 'Pregunta: '.$pregunta."\n\nCONTEXTO:\n"
            .json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function nombreFormato(string $formato): string
    {
        return $formato === 'xlsx' ? 'Excel' : 'PDF';
    }

    private function instrucciones(): string
    {
        return <<<'TXT'
        Eres el asistente de preparación para appraisals CMMI V3.0 de la empresa.
        Respondes en español, con tono profesional y claro.
        Usa exclusivamente los datos del CONTEXTO para hablar del appraisal. Si la pregunta pide datos que no están en el contexto, dilo sin inventarlos.
        Si el contexto incluye una nota, menciónala.

        La plataforma genera los archivos de reporte por su cuenta; tú no tienes que generarlos.
        Si "archivo_generado" tiene un valor, el reporte en ese formato ya quedó listo para descargar: confírmalo en "resumen_voz".
        Nunca digas que no puedes generar un PDF o un Excel.

        Responde solo con un objeto JSON con estas claves:
        - "resumen_voz": una o dos frases cortas, sin Markdown, pensadas para leerse en voz alta.
        - "analisis_markdown": un análisis breve en Markdown de máximo cinco viñetas con los hallazgos más relevantes. No repitas la tabla completa de datos.
        TXT;
    }
}
