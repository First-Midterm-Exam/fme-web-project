<?php

namespace App\Actions\Evidences;

use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\EvidenceVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class AddEvidenceVersionAction
{
    public function __construct(private readonly UploadEvidenceFileAction $uploader) {}

    /**
     * @throws ValidationException
     */
    public function execute(Evidence $evidence, User $user, UploadedFile $file): EvidenceVersion
    {
        $project = $evidence->project;

        $hasActiveAppraisal = $project->appraisals()
            ->where('status', Appraisal::STATUS_ACTIVO)
            ->exists();

        if (! $hasActiveAppraisal) {
            throw ValidationException::withMessages([
                'file' => 'El proyecto de la evidencia no tiene un appraisal en curso (RN-10).',
            ]);
        }

        $uploaded = $this->uploader->execute($project, $file);

        try {
            return DB::transaction(function () use ($evidence, $user, $uploaded): EvidenceVersion {
                Evidence::whereKey($evidence->id)->lockForUpdate()->first();

                $number = (int) EvidenceVersion::where('evidence_id', $evidence->id)->max('number') + 1;

                $version = EvidenceVersion::create([
                    'evidence_id' => $evidence->id,
                    'number' => $number,
                    'file_public_id' => $uploaded['public_id'],
                    'file_url' => $uploaded['url'],
                    'file_resource_type' => $uploaded['resource_type'],
                    'file_format' => $uploaded['format'],
                    'file_original_name' => $uploaded['original_name'],
                    'file_size' => $uploaded['size'],
                    'uploaded_by' => $user->id,
                    'uploaded_at' => now(),
                ]);

                $evidence->touch();

                return $version;
            });
        } catch (Throwable $e) {
            $this->uploader->discard($uploaded);

            throw $e;
        }
    }
}
