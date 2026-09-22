<?php

namespace App\Actions\Evidences;

use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyEvidenceAction
{
    /**
     * @throws ValidationException
     */
    public function execute(Evidence $evidence, User $verifier, int $statusId, ?string $reason = null): Evidence
    {
        $allowedStatuses = [
            EvidenceStatus::VERIFICADA,
            EvidenceStatus::OBSERVADA,
            EvidenceStatus::RECHAZADA,
        ];

        if (! in_array($statusId, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'verification_status' => 'El estado de verificación seleccionado no es válido.',
            ]);
        }

        if ((int) $evidence->status_id !== EvidenceStatus::REGISTRADA) {
            throw ValidationException::withMessages([
                'evidence' => 'La evidencia no se encuentra en estado Registrada o ya ha sido evaluada.',
            ]);
        }

        $trimmedReason = is_string($reason) ? trim($reason) : null;

        if (in_array($statusId, [EvidenceStatus::OBSERVADA, EvidenceStatus::RECHAZADA], true)) {
            if ($trimmedReason === null || $trimmedReason === '') {
                throw ValidationException::withMessages([
                    'verification_reason' => 'Debe ingresar el motivo de la justificación al observar o rechazar la evidencia.',
                ]);
            }
        }

        return DB::transaction(function () use ($evidence, $verifier, $statusId, $trimmedReason): Evidence {
            /** @var Evidence $lockedEvidence */
            $lockedEvidence = Evidence::whereKey($evidence->id)->lockForUpdate()->firstOrFail();

            if ((int) $lockedEvidence->status_id !== EvidenceStatus::REGISTRADA) {
                throw ValidationException::withMessages([
                    'evidence' => 'La evidencia no se encuentra en estado Registrada o ya ha sido evaluada.',
                ]);
            }

            $lockedEvidence->update([
                'status_id' => $statusId,
                'verification_reason' => $trimmedReason !== '' ? $trimmedReason : null,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);

            return $lockedEvidence->fresh(['status', 'verifiedBy']);
        });
    }
}
