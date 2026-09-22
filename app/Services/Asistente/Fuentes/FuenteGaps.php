<?php

namespace App\Services\Asistente\Fuentes;

use App\Models\Appraisal;

interface FuenteGaps
{
    /**
     * @return list<array{codigo: string, titulo: string, practica: string, severidad: string|null, estado: string, fecha_limite: string|null}>
     */
    public function gapsDe(Appraisal $appraisal): array;
}
