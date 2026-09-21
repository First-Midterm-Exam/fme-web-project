<?php

namespace App\Services\Documentos;

use Illuminate\Http\UploadedFile;

interface RevisorFormato
{
    /**
     * @return array<string, mixed>
     */
    public function revisar(UploadedFile $imagen): array;
}
