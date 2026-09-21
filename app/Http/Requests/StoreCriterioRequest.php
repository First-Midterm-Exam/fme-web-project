<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCriterioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('evaluar-cumplimiento') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('practice_criteria', 'code')
                    ->where('practice_id', $this->route('practice')?->id)
                    ->ignore($this->route('criterio')),
            ],
            'descripcion' => ['required', 'string'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'required' => ['nullable', 'boolean'],
            'estado' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del criterio es obligatorio.',
            'code.unique' => 'Ya existe un criterio con ese código en esta práctica.',
            'descripcion.required' => 'La descripción del criterio debe ser ingresada obligatoriamente.',
        ];
    }
}
