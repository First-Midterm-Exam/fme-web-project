<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EvidenceDownloadController extends Controller
{
    public function __invoke(Evidence $evidence): RedirectResponse
    {
        $user = auth()->user();

        if (! $user || ! Gate::forUser($user)->allows('download', $evidence)) {
            abort(403, 'Acceso denegado.');
        }

        $version = $evidence->currentVersion;

        if (! $version) {
            abort(404, 'No se encontró la versión del archivo.');
        }

        $expiresAt = time() + 300;

        $downloadUrl = Cloudinary::uploadApi()->privateDownloadUrl(
            $version->file_public_id,
            $version->file_format ?? '',
            [
                'type' => 'authenticated',
                'resource_type' => $version->file_resource_type,
                'expires_at' => $expiresAt,
                'attachment' => true,
            ]
        );

        return redirect()->away($downloadUrl);
    }
}
