<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCriterioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación aplicadas a la solicitud.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'practica_id' => ['required', 'integer'],
            'descripcion' => ['required', 'string'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'estado' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción del criterio debe ser ingresada obligatoriamente.',
            'practica_id.required' => 'Debe asociar el criterio a una práctica.',
        ];
    }
}
