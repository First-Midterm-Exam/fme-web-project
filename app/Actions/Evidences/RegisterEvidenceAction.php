<?php

namespace App\Actions\Evidences;

use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\EvidenceVersion;
use App\Models\Project;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterEvidenceAction
{
    /**
     * @param  array{name: string, type: string, description?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function execute(Project $project, User $user, UploadedFile $file, array $data): Evidence
    {
        $hasActiveAppraisal = $project->appraisals()
            ->where('status', Appraisal::STATUS_ACTIVO)
            ->exists();

        if (! $hasActiveAppraisal) {
            throw ValidationException::withMessages([
                'project_id' => 'El proyecto seleccionado no tiene un appraisal en curso (RN-10).',
            ]);
        }

        try {
            $uploadResult = Cloudinary::uploadApi()->upload(
                $file->getRealPath(),
                [
                    'type' => 'authenticated',
                    'folder' => "evidencias/{$project->id}",
                    'resource_type' => 'auto',
                    'use_filename' => true,
                    'unique_filename' => true,
                ]
            );
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Invalid api_key') || str_contains($e->getMessage(), 'AuthorizationRequired')) {
                throw ValidationException::withMessages([
                    'file' => 'Error de autenticación con Cloudinary: la URL configurada en el archivo .env contiene credenciales no válidas o de ejemplo (ej. <your_api_key>).',
                ]);
            }

            throw ValidationException::withMessages([
                'file' => 'Error al subir el archivo a Cloudinary: '.$e->getMessage(),
            ]);
        }

        $publicId = $uploadResult['public_id'];
        $resourceType = $uploadResult['resource_type'] ?? 'raw';
        $format = $uploadResult['format'] ?? strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $fileSize = $uploadResult['bytes'] ?? $file->getSize();

        try {
            return DB::transaction(function () use ($project, $user, $data, $publicId, $resourceType, $format, $originalName, $fileSize): Evidence {
                $code = Evidence::generateNextCode();

                $evidence = Evidence::create([
                    'code' => $code,
                    'project_id' => $project->id,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'description' => $data['description'] ?? null,
                    'status_id' => EvidenceStatus::REGISTRADA,
                    'uploaded_by' => $user->id,
                ]);

                EvidenceVersion::create([
                    'evidence_id' => $evidence->id,
                    'number' => 1,
                    'file_public_id' => $publicId,
                    'file_resource_type' => $resourceType,
                    'file_format' => $format,
                    'file_original_name' => $originalName,
                    'file_size' => $fileSize,
                    'uploaded_at' => now(),
                ]);

                return $evidence;
            });
        } catch (\Throwable $e) {
            try {
                Cloudinary::uploadApi()->destroy($publicId, [
                    'type' => 'authenticated',
                    'resource_type' => $resourceType,
                ]);
            } catch (\Throwable $cleanupException) {
                // Ignore cleanup failure to throw original exception
            }

            throw $e;
        }
    }
}
