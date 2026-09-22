<?php

namespace App\Livewire\Evidences;

use App\Actions\Evidences\AddEvidenceVersionAction;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;

class EvidenceDetail extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Evidence $evidence;

    /**
     * @var mixed
     */
    public $file;

    public function mount(Evidence $evidence): void
    {
        $this->authorize('view', $evidence);

        $this->evidence = $evidence;
    }

    public function uploadVersion(AddEvidenceVersionAction $action): void
    {
        $this->authorize('update', $this->evidence);

        $this->validate([
            'file' => ['required', 'file', 'max:'.Evidence::MAX_FILE_SIZE_KB, 'mimes:'.implode(',', Evidence::ALLOWED_EXTENSIONS)],
        ], [
            'file.required' => 'Debe adjuntar el archivo de la nueva versión.',
            'file.file' => 'El archivo adjunto no es válido.',
            'file.max' => 'El archivo supera el tamaño máximo permitido de 10 MB.',
            'file.mimes' => 'El tipo de archivo no está permitido. Tipos permitidos: PDF, Word, Excel, PowerPoint, imágenes (PNG, JPG, JPEG) y texto (TXT).',
        ]);

        /** @var User $user */
        $user = auth()->user();

        $version = $action->execute($this->evidence, $user, $this->file);

        $this->reset('file');
        $this->evidence->refresh();

        session()->flash('message', 'Se registró la versión '.$version->number.' de la evidencia. Las versiones anteriores se conservan en el historial.');
    }

    public function render(): View
    {
        $this->authorize('view', $this->evidence);

        $this->evidence->load(['project', 'status', 'uploadedBy', 'practices', 'currentVersion']);

        return view('livewire.evidences.evidence-detail', [
            'versions' => $this->evidence->versions()->with('uploadedBy')->orderByDesc('number')->get(),
            'types' => Evidence::TYPES,
        ])->layout('layouts.app', ['header' => 'Detalle de Evidencia']);
    }
}
