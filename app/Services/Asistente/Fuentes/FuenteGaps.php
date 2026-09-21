<?php

namespace App\Services\Asistente\Fuentes;

use App\Models\Appraisal;

interface FuenteGaps
{
    /**
     * @return list<array{codigo: string, titulo: string, practica: string, severidad: string, estado: string, fecha_limite: string}>
     */
    public function gapsDe(Appraisal $appraisal): array;

    public function esDePrueba(): bool;
}
