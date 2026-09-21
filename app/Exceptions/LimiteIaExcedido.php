<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class LimiteIaExcedido extends RuntimeException
{
    public function __construct(public readonly int $segundos = 30)
    {
        parent::__construct('Límite de consultas al servicio de generación excedido.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Se alcanzó el límite de consultas. Intenta nuevamente en unos segundos.',
        ], 429, ['Retry-After' => (string) $this->segundos]);
    }
}
