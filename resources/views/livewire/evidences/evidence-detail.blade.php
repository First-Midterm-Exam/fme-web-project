<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('evidencias.index') }}" class="text-decoration-none text-info">Evidencias</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $evidence->code }}</li>
                </ol>
            </nav>
            <h1 class="h3 text-white fw-bold mb-1">
                <i class="bi bi-file-earmark-text me-2 text-info"></i>{{ $evidence->name }}
            </h1>
            <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                <span class="font-monospace text-info">{{ $evidence->code }}</span>
                <span>•</span>
                <span>{{ $types[$evidence->type] ?? $evidence->type }}</span>
                <span>•</span>
                <span class="badge bg-success bg-opacity-25 text-success">{{ $evidence->status->name }}</span>
                @if ($evidence->currentVersion)
                    <span>•</span>
                    <span class="badge bg-info text-dark">Versión vigente v{{ $evidence->currentVersion->number }}</span>
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('evidencias.download', $evidence) }}" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="bi bi-download me-1"></i>Descargar vigente
            </a>
            <a href="{{ route('evidencias.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Evidencias
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                <div class="card-header bg-dark border-secondary py-3">
                    <h2 class="h6 mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-info"></i>Información de la evidencia</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-secondary">Proyecto</dt>
                        <dd class="col-sm-8">{{ $evidence->project->code }} — {{ $evidence->project->name }}</dd>

                        <dt class="col-sm-4 text-secondary">Registrada por</dt>
                        <dd class="col-sm-8">{{ $evidence->uploadedBy->name }}</dd>

                        <dt class="col-sm-4 text-secondary">Fecha de registro</dt>
                        <dd class="col-sm-8">{{ $evidence->created_at?->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4 text-secondary">Descripción</dt>
                        <dd class="col-sm-8">{{ $evidence->description ?: 'Sin descripción.' }}</dd>

                        <dt class="col-sm-4 text-secondary">Prácticas asociadas</dt>
                        <dd class="col-sm-8 mb-0">
                            @forelse ($evidence->practices as $practice)
                                <span class="badge bg-secondary bg-opacity-50 text-info font-monospace me-1 mb-1">{{ $practice->code }}</span>
                            @empty
                                <span class="text-secondary">Sin prácticas asociadas.</span>
                            @endforelse
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                <div class="card-header bg-dark border-secondary py-3">
                    <h2 class="h6 mb-0 fw-bold"><i class="bi bi-cloud-arrow-up me-2 text-info"></i>Subir nueva versión</h2>
                </div>
                <div class="card-body">
                    @can('update', $evidence)
                        <form wire:submit="uploadVersion">
                            <p class="text-secondary small">
                                La nueva versión pasa a ser la vigente. Las versiones anteriores se conservan y siguen disponibles en el historial.
                            </p>

                            <div class="mb-3">
                                <label for="file" class="form-label fw-semibold">Archivo <span class="text-danger">*</span></label>
                                <input type="file" id="file" wire:model="file" class="form-control bg-dark text-white border-secondary @error('file') is-invalid @enderror">
                                <small class="text-secondary">Máx 10 MB. PDF, Word, Excel, PPT, imágenes o TXT.</small>
                                @error('file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div wire:loading wire:target="file" class="small text-info mt-1">
                                    <span class="spinner-border spinner-border-sm me-1"></span>Cargando archivo...
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary fw-semibold" wire:loading.attr="disabled" wire:target="file,uploadVersion">
                                <span wire:loading.remove wire:target="uploadVersion"><i class="bi bi-upload me-1"></i>Subir versión</span>
                                <span wire:loading wire:target="uploadVersion"><span class="spinner-border spinner-border-sm me-1"></span>Subiendo...</span>
                            </button>
                        </form>
                    @else
                        <p class="text-secondary small mb-0">
                            <i class="bi bi-lock me-1"></i>No puedes subir nuevas versiones de esta evidencia. Solo se permite a usuarios con acceso al proyecto y mientras tenga un appraisal en curso.
                        </p>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold">
                <i class="bi bi-clock-history me-2 text-info"></i>Historial de versiones
                <span class="badge bg-secondary bg-opacity-50 text-light ms-2">{{ $versions->count() }}</span>
            </h2>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small border-secondary">
                        <th>Versión</th>
                        <th>Archivo</th>
                        <th>Formato</th>
                        <th>Tamaño</th>
                        <th>Subida por</th>
                        <th>Fecha</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($versions as $version)
                        <tr class="border-secondary">
                            <td>
                                <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">v{{ $version->number }}</span>
                                @if ($loop->first)
                                    <span class="badge bg-info text-dark ms-1">Vigente</span>
                                @endif
                            </td>
                            <td class="text-break">{{ $version->file_original_name }}</td>
                            <td><span class="text-uppercase small">{{ $version->file_format ?? '—' }}</span></td>
                            <td class="small">{{ $version->formattedSize() }}</td>
                            <td class="small">{{ $version->uploadedBy?->name ?? $evidence->uploadedBy->name }}</td>
                            <td class="small text-secondary">{{ $version->uploaded_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('evidencias.versions.download', [$evidence, $version]) }}"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-info"
                                   title="Descargar esta versión (enlace temporal de 5 minutos)">
                                    <i class="bi bi-download"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>Esta evidencia no tiene versiones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
