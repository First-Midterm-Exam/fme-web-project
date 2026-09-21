<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 font-weight-bold text-white mb-1">Registro de Evidencias</h2>
            <p class="text-secondary mb-0">Gestión de evidencias documentales para preparación CMMI de DIMA LTDA.</p>
        </div>
        @can('create', App\Models\Evidence::class)
            <div>
                <button wire:click="openCreateModal" class="btn btn-primary d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-file-earmark-plus"></i> Registrar Evidencia
                </button>
            </div>
        @endcan
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center justify-content-between mb-3">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-secondary border-secondary border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               class="form-control bg-dark text-white border-secondary border-start-0"
                               placeholder="Buscar por código o nombre...">
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <select wire:model.live="projectFilter" class="form-select bg-dark text-white border-secondary">
                        <option value="all">— Todos los Proyectos —</option>
                        @foreach ($eligibleProjects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->code }} - {{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 border-secondary">
                    <thead>
                        <tr class="border-secondary text-secondary">
                            <th scope="col" style="width: 12%;">Código</th>
                            <scope scope="col">Nombre & Tipo</th>
                            <th scope="col">Proyecto</th>
                            <th scope="col">Responsable</th>
                            <th scope="col">Fecha Carga</th>
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($evidences as $evidence)
                            <tr>
                                <td>
                                    <span class="badge bg-primary bg-opacity-25 text-primary font-monospace fs-6 px-2 py-1">
                                        {{ $evidence->code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-white">{{ $evidence->name }}</div>
                                    <small class="text-secondary">
                                        <i class="bi bi-tag me-1"></i>{{ ucfirst($evidence->type) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="text-info fw-semibold">{{ $evidence->project->code }}</span>
                                    <div class="small text-secondary">{{ Str::limit($evidence->project->name, 25) }}</div>
                                </td>
                                <td>
                                    <div class="text-white small">{{ $evidence->uploadedBy->name }}</div>
                                    <div class="text-secondary small">{{ $evidence->uploadedBy->email }}</div>
                                </td>
                                <td>
                                    <small class="text-secondary">
                                        {{ $evidence->currentVersion ? $evidence->currentVersion->uploaded_at->format('d/m/Y H:i') : $evidence->created_at->format('d/m/Y H:i') }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-25 text-success">
                                        {{ $evidence->status->name }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('evidencias.download', $evidence) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-info"
                                       title="Descargar archivo privado (link temporal 5 min)">
                                        <i class="bi bi-download me-1"></i> Descargar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    No se encontraron evidencias registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $evidences->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Registro Evidencia -->
    @if ($showCreateModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title font-weight-bold">
                            <i class="bi bi-file-earmark-arrow-up text-primary me-2"></i> Registrar Nueva Evidencia
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCreateModal"></button>
                    </div>

                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            @if ($eligibleProjects->isEmpty())
                                <div class="alert alert-warning mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>No hay proyectos disponibles con appraisal en curso (RN-10).</strong><br>
                                    Para registrar una evidencia, el proyecto debe tener una evaluación (Appraisal) en estado <strong>activo</strong>. Ve a la sección de <em>Appraisals</em> para activar una.
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="projectId" class="form-label text-secondary fw-semibold">
                                    Proyecto con Appraisal en Curso <span class="text-danger">*</span>
                                </label>
                                <select wire:model="projectId" id="projectId" class="form-select bg-dark text-white border-secondary @error('projectId') is-invalid @enderror">
                                    <option value="">— Seleccionar Proyecto —</option>
                                    @foreach ($eligibleProjects as $proj)
                                        <option value="{{ $proj->id }}">{{ $proj->code }} - {{ $proj->name }}</option>
                                    @endforeach
                                    @if ($projectId && ! $eligibleProjects->contains('id', $projectId) && ($selectedProj = \App\Models\Project::find($projectId)))
                                        <option value="{{ $selectedProj->id }}">{{ $selectedProj->code }} - {{ $selectedProj->name }}</option>
                                    @endif
                                </select>
                                @error('projectId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label for="name" class="form-label text-secondary fw-semibold">
                                        Nombre de la Evidencia <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           wire:model="name"
                                           id="name"
                                           class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror"
                                           placeholder="Ej: Plan de Pruebas Unitarias v1">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label text-secondary fw-semibold">
                                        Tipo <span class="text-danger">*</span>
                                    </label>
                                    <select wire:model="type" id="type" class="form-select bg-dark text-white border-secondary @error('type') is-invalid @enderror">
                                        <option value="">— Seleccionar Tipo —</option>
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label text-secondary fw-semibold">Descripción (Opcional)</label>
                                <textarea wire:model="description"
                                          id="description"
                                          rows="3"
                                          class="form-control bg-dark text-white border-secondary @error('description') is-invalid @enderror"
                                          placeholder="Detalle o contexto adicional de la evidencia..."></textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="file" class="form-label text-secondary fw-semibold">
                                    Archivo Adjunto <span class="text-danger">*</span>
                                    <small class="text-muted ms-1">(Máx 10 MB. PDF, Word, Excel, PPT, Imágenes, TXT)</small>
                                </label>
                                <input type="file"
                                       wire:model="file"
                                       id="file"
                                       class="form-control bg-dark text-white border-secondary @error('file') is-invalid @enderror">

                                <div wire:loading wire:target="file" class="text-info small mt-1">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Cargando archivo temporal...
                                </div>

                                @error('file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeCreateModal">Cancelar</button>
                            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2" wire:loading.attr="disabled">
                                <span wire:loading wire:target="save" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i wire:loading.remove wire:target="save" class="bi bi-cloud-arrow-up"></i>
                                Guardar Evidencia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
