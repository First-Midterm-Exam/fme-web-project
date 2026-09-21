<?php

namespace App\Services\Asistente;

use App\Services\Ia\ClienteGroq;
use Illuminate\Support\Str;

class ClasificadorConsulta
{
    private const PALABRAS_TIPO = [
        CatalogoReportes::GAPS => ['gap', 'brecha'],
        CatalogoReportes::EVIDENCIAS => ['evidencia'],
        CatalogoReportes::CRITERIOS => ['criterio', 'no cumpl', 'incumpl', 'pendiente'],
        CatalogoReportes::CUMPLIMIENTO => ['practica', 'cumplimiento', 'avance'],
        CatalogoReportes::RESUMEN => ['resumen', 'readiness', 'general', 'estado del appraisal', 'como vamos'],
    ];

    private const PALABRAS_ARCHIVO = ['reporte', 'informe', 'genera', 'descarga', 'archivo', 'documento', 'exporta'];

    public function __construct(private readonly ClienteGroq $groq) {}

    /**
     * @return array{tipo: string, formato: string|null}
     */
    public function clasificar(string $pregunta): array
    {
        $heuristica = $this->heuristica($pregunta);

        if (! $this->groq->configurado()) {
            return $heuristica;
        }

        $respuesta = $this->groq->json([
            ['role' => 'system', 'content' => $this->instrucciones()],
            ['role' => 'user', 'content' => $pregunta],
        ]);

        $tipo = $respuesta['tipo_reporte'] ?? null;
        $formato = $respuesta['formato'] ?? null;

        return [
            'tipo' => is_string($tipo) && CatalogoReportes::existe($tipo) ? $tipo : $heuristica['tipo'],
            'formato' => $this->formatoExplicito($pregunta)
                ?? (in_array($formato, ['pdf', 'xlsx'], true) ? $formato : null),
        ];
    }

    /**
     * @return array{tipo: string, formato: string|null}
     */
    public function heuristica(string $pregunta): array
    {
        $texto = $this->normalizar($pregunta);
        $tipo = CatalogoReportes::GENERAL;

        foreach (self::PALABRAS_TIPO as $candidato => $palabras) {
            if (Str::contains($texto, $palabras)) {
                $tipo = $candidato;
                break;
            }
        }

        $formato = $this->formatoExplicito($pregunta)
            ?? (Str::contains($texto, self::PALABRAS_ARCHIVO) ? 'pdf' : null);

        return ['tipo' => $tipo, 'formato' => $formato];
    }

    private function formatoExplicito(string $pregunta): ?string
    {
        $texto = $this->normalizar($pregunta);

        if (Str::contains($texto, ['excel', 'xlsx', 'hoja de calculo'])) {
            return 'xlsx';
        }

        return Str::contains($texto, 'pdf') ? 'pdf' : null;
    }

    private function normalizar(string $texto): string
    {
        return Str::of($texto)->ascii()->lower()->toString();
    }

    private function instrucciones(): string
    {
        $catalogo = collect(CatalogoReportes::TIPOS)
            ->map(fn (array $tipo, string $clave): string => '- '.$clave.': '.$tipo['descripcion'])
            ->implode("\n");

        return <<<TXT
        Clasificas preguntas sobre un appraisal CMMI V3.0 para decidir qué información recuperar.
        Tipos de reporte disponibles:
        {$catalogo}

        Responde solo con un objeto JSON con estas claves:
        - "tipo_reporte": una de las claves anteriores.
        - "formato": "pdf" o "xlsx" si la persona pide generar, descargar o exportar un reporte o archivo; null si solo pide información.
        Si pide un reporte sin indicar formato, usa "pdf".
        TXT;
    }
}
