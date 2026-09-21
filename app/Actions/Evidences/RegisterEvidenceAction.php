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
    public function execute(Project $project, User $user, UploadedFile $file, array $data, array $practiceIds = []): Evidence
    {
        $hasActiveAppraisal = $project->appraisals()
            ->where('status', Appraisal::STATUS_ACTIVO)
            ->exists();

        if (! $hasActiveAppraisal) {
            throw ValidationException::withMessages([
                'project_id' => 'El proyecto seleccionado no tiene un appraisal en curso (RN-10).',
            ]);
        }

        // Subida local simulada sin depender de Cloudinary
        $publicId = 'local_'.uniqid();
        $resourceType = 'raw';
        $format = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        try {
            return DB::transaction(function () use ($project, $user, $data, $publicId, $resourceType, $format, $originalName, $fileSize, $practiceIds): Evidence {
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
                if (! empty($practiceIds)) {
                    $evidence->practices()->sync($practiceIds);
                }

                return $evidence;
            });
        } catch (\Throwable $e) {
            // try {
            // Cloudinary::uploadApi()->destroy($publicId, [
            // 'type' => 'authenticated',
            // 'resource_type' => $resourceType,
            // ]);
            // } catch (\Throwable $cleanupException) {
            // Ignore cleanup failure to throw original exception
            // }

            throw $e;
        }
    }
}
