<?php

namespace App\Http\Requests;

use App\Models\Appraisal;
use Illuminate\Foundation\Http\FormRequest;

class EjecutarSimulacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appraisal = $this->route('appraisal');

        return $appraisal instanceof Appraisal
            && ($this->user()?->can('simulate', $appraisal) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nivelObjetivo' => ['required', 'integer', 'between:1,5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nivelObjetivo' => 'nivel objetivo',
        ];
    }
}
