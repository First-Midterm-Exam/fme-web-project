<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ServicioIaNoDisponible extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'El servicio de generación no está disponible en este momento.',
        ], 503);
    }
}
