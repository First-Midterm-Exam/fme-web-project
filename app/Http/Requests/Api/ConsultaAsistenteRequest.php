<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaAsistenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'appraisal_id' => ['required', 'integer', 'exists:appraisals,id'],
            'pregunta' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appraisal_id.required' => 'Debes indicar el appraisal a consultar.',
            'appraisal_id.exists' => 'El appraisal indicado no existe.',
            'pregunta.required' => 'La pregunta no puede estar vacía.',
            'pregunta.max' => 'La pregunta no puede superar los 1000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('pregunta'))) {
            $this->merge(['pregunta' => trim($this->input('pregunta'))]);
        }
    }
}
