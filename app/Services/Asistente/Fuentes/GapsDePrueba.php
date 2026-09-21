<?php

namespace App\Services\Asistente\Fuentes;

use App\Models\Appraisal;
use Illuminate\Support\Carbon;

class GapsDePrueba implements FuenteGaps
{
    private const PLANTILLAS = [
        ['titulo' => 'Estimaciones del proyecto sin evidencia de respaldo', 'severidad' => 'alta', 'estado' => 'Abierto', 'dias' => 10],
        ['titulo' => 'Cronograma sin aprobación formal del patrocinador', 'severidad' => 'alta', 'estado' => 'En progreso', 'dias' => 15],
        ['titulo' => 'Matriz de responsabilidades incompleta', 'severidad' => 'media', 'estado' => 'Abierto', 'dias' => 20],
        ['titulo' => 'Registro de riesgos sin actualización en el último mes', 'severidad' => 'alta', 'estado' => 'Abierto', 'dias' => 7],
        ['titulo' => 'Acta de revisión sin firmas de los participantes', 'severidad' => 'baja', 'estado' => 'Abierto', 'dias' => 30],
    ];

    /**
     * @return list<array{codigo: string, titulo: string, practica: string, severidad: string, estado: string, fecha_limite: string}>
     */
    public function gapsDe(Appraisal $appraisal): array
    {
        $codigos = $appraisal->practices()->orderBy('practices.code')->pluck('practices.code')->values()->all();
        $referencia = Carbon::parse($appraisal->target_date);

        $gaps = [];

        foreach (self::PLANTILLAS as $indice => $plantilla) {
            $gaps[] = [
                'codigo' => sprintf('GAP-%03d', $indice + 1),
                'titulo' => $plantilla['titulo'],
                'practica' => (string) ($codigos[$indice % max(count($codigos), 1)] ?? 'PLAN 2.1'),
                'severidad' => $plantilla['severidad'],
                'estado' => $plantilla['estado'],
                'fecha_limite' => $referencia->copy()->subDays(60 - $plantilla['dias'])->format('Y-m-d'),
            ];
        }

        return $gaps;
    }

    public function esDePrueba(): bool
    {
        return true;
    }
}
