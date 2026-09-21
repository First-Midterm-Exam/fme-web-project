<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class RevisionFormatoRequest extends FormRequest
{
    public const MAXIMO_KB = 5120;

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
            'imagen' => ['required', 'file', 'image', 'mimes:jpg,jpeg'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $imagen = $this->file('imagen');

        abort_if(
            $imagen instanceof UploadedFile && $imagen->getSize() > self::MAXIMO_KB * 1024,
            413,
            'La imagen supera el tamaño máximo permitido de 5 MB.'
        );
    }
}
