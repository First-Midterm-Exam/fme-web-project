<?php

namespace App\Actions\Evidences;

use App\Models\Project;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

class UploadEvidenceFileAction
{
    /**
     * @return array{public_id: string, resource_type: string, format: string, original_name: string, size: int, url: string|null}
     *
     * @throws ValidationException
     */
    public function execute(Project $project, UploadedFile $file): array
    {
        try {
            $result = Cloudinary::uploadApi()->upload(
                $file->getRealPath(),
                [
                    'type' => 'authenticated',
                    'folder' => "evidencias/{$project->id}",
                    'resource_type' => 'auto',
                    'use_filename' => true,
                    'unique_filename' => true,
                ]
            );
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Invalid api_key') || str_contains($e->getMessage(), 'AuthorizationRequired')) {
                throw ValidationException::withMessages([
                    'file' => 'Error de autenticación con Cloudinary: la URL configurada en el archivo .env contiene credenciales no válidas o de ejemplo (ej. <your_api_key>).',
                ]);
            }

            throw ValidationException::withMessages([
                'file' => 'Error al subir el archivo a Cloudinary: '.$e->getMessage(),
            ]);
        }

        return [
            'public_id' => (string) $result['public_id'],
            'resource_type' => (string) ($result['resource_type'] ?? 'raw'),
            'format' => (string) ($result['format'] ?? strtolower($file->getClientOriginalExtension())),
            'original_name' => $file->getClientOriginalName(),
            'size' => (int) ($result['bytes'] ?? $file->getSize()),
            'url' => isset($result['secure_url']) ? (string) $result['secure_url'] : null,
        ];
    }

    /**
     * @param  array{public_id: string, resource_type: string}  $uploaded
     */
    public function discard(array $uploaded): void
    {
        try {
            Cloudinary::uploadApi()->destroy($uploaded['public_id'], [
                'type' => 'authenticated',
                'resource_type' => $uploaded['resource_type'],
            ]);
        } catch (Throwable) {
            return;
        }
    }
}
