<?php

namespace App\Services\Asistente\Fuentes;

use App\Models\Appraisal;
use App\Models\Gap;

class GapsRegistrados implements FuenteGaps
{
    /**
     * @return list<array{codigo: string, titulo: string, practica: string, severidad: string|null, estado: string, fecha_limite: string|null}>
     */
    public function gapsDe(Appraisal $appraisal): array
    {
        return Gap::query()
            ->abiertos()
            ->with('practiceEvaluation.practice')
            ->whereHas('practiceEvaluation', fn ($query) => $query->where('appraisal_id', $appraisal->id))
            ->orderBy('code')
            ->get()
            ->map(fn (Gap $gap): array => [
                'codigo' => $gap->code,
                'titulo' => $gap->title,
                'practica' => $gap->practiceEvaluation->practice->code,
                'severidad' => null,
                'estado' => $gap->status,
                'fecha_limite' => null,
            ])
            ->values()
            ->all();
    }
}
