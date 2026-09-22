<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 font-weight-bold text-white mb-1">Verificación de Evidencias</h2>
            <p class="text-secondary mb-0">Revisión y verificación de evidencias pendientes (estado Registrada) para proyectos asignados.</p>
        </div>
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
                        <option value="all">— Todos los Proyectos Asignados —</option>
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
                            <th scope="col">Nombre & Tipo</th>
                            <th scope="col">Prácticas CMMI</th>
                            <th scope="col">Proyecto</th>
                            <th scope="col">Cargado Por</th>
                            <th scope="col">Fecha Carga</th>
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pendingEvidences as $evidence)
                            <tr>
                                <td>
                                    <span class="badge bg-primary bg-opacity-25 text-primary font-monospace fs-6 px-2 py-1">
                                        {{ $evidence->code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-white">{{ $evidence->name }}</div>
                                    <small class="text-secondary">
                                        <i class="bi bi-tag me-1"></i>{{ $types[$evidence->type] ?? ucfirst($evidence->type) }}
                                    </small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse ($evidence->practices as $practice)
                                            <span class="badge bg-secondary text-light font-monospace" style="font-size: 0.72rem;" title="{{ $practice->name }}">
                                                {{ $practice->code }}
                                            </span>
                                        @empty
                                            <span class="text-muted" style="font-size: 0.72rem;">Sin prácticas</span>
                                        @endforelse
                                    </div>
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
                                    <span class="badge bg-warning bg-opacity-25 text-warning">
                                        {{ $evidence->status->name }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button wire:click="openDetailModal({{ $evidence->id }})"
                                                class="btn btn-outline-light"
                                                title="Ver detalle de la evidencia y archivo">
                                            <i class="bi bi-eye me-1"></i> Detalle
                                        </button>
                                        <a href="{{ route('evidencias.download', $evidence) }}"
                                           target="_blank"
                                           class="btn btn-outline-info"
                                           title="Descargar archivo vigente (v{{ $evidence->currentVersion?->number ?? 1 }})">
                                            <i class="bi bi-download me-1"></i> Archivo
                                        </a>
                                        @can('verify', $evidence)
                                            <button wire:click="openVerifyModal({{ $evidence->id }})"
                                                    class="btn btn-success fw-semibold"
                                                    title="Verificar, observar o rechazar esta evidencia">
                                                <i class="bi bi-patch-check me-1"></i> Verificar
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-secondary">
                                    <i class="bi bi-check2-all fs-2 d-block mb-2 text-muted"></i>
                                    No hay evidencias pendientes de verificación para los proyectos asignados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $pendingEvidences->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Detalle de Evidencia -->
    @if ($showDetailModal && $selectedEvidence)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title font-weight-bold">
                            <i class="bi bi-info-circle text-info me-2"></i> Detalle de Evidencia {{ $selectedEvidence->code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDetailModal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="text-secondary small fw-semibold d-block">Nombre</label>
                                <span class="fs-5 fw-bold text-white">{{ $selectedEvidence->name }}</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-secondary small fw-semibold d-block">Tipo</label>

                                <span class="badge bg-secondary text-light fs-6">
                                    {{ $types[$selectedEvidence->type] ?? ucfirst($selectedEvidence->type) }}
                                </span>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="text-secondary small fw-semibold d-block">Proyecto</label>
                                <span class="text-info fw-semibold">{{ $selectedEvidence->project->code }}</span> - {{ $selectedEvidence->project->name }}
                            </div>
                            <div class="col-md-6">
                                <label class="text-secondary small fw-semibold d-block">Cargado Por</label>
                                <span class="text-white">{{ $selectedEvidence->uploadedBy->name }}</span>
                                <span class="text-secondary small">({{ $selectedEvidence->uploadedBy->email }})</span>
                            </div>
                        </div>

                        @if ($selectedEvidence->description)
                            <div class="mb-3">
                                <label class="text-secondary small fw-semibold d-block">Descripción</label>
                                <div class="bg-dark border border-secondary rounded p-2 text-light small">
                                    {{ $selectedEvidence->description }}
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="text-secondary small fw-semibold d-block">Prácticas CMMI Asociadas</label>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                @forelse ($selectedEvidence->practices as $practice)
                                    <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 p-2">
                                        <strong>{{ $practice->code }}</strong> — {{ $practice->name }}
                                    </span>
                                @empty
                                    <span class="text-muted small">No tiene prácticas CMMI asociadas.</span>
                                @endforelse
                            </div>
                        </div>

                        <hr class="border-secondary my-3">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-white">Versión Vigente (v{{ $selectedEvidence->currentVersion?->number ?? 1 }})</h6>
                            <a href="{{ route('evidencias.download', $selectedEvidence) }}"
                               target="_blank"
                               class="btn btn-sm btn-outline-info">
                                <i class="bi bi-download me-1"></i> Descargar Archivo Vigente
                            </a>
                        </div>

                        @if ($selectedEvidence->currentVersion)
                            <div class="bg-dark border border-secondary rounded p-3 small text-secondary">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <strong>Nombre archivo:</strong> <span class="text-white">{{ $selectedEvidence->currentVersion->file_original_name }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Formato:</strong> <span class="text-uppercase text-white">{{ $selectedEvidence->currentVersion->file_format ?? 'n/a' }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Tamaño:</strong> <span class="text-white">{{ number_format($selectedEvidence->currentVersion->file_size / 1024, 1) }} KB</span>
                                    </div>
                                    <div class="col-md-12">
                                        <strong>Fecha de Subida:</strong> <span class="text-white">{{ $selectedEvidence->currentVersion->uploaded_at->format('d/m/Y H:i:s') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeDetailModal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Verificar Evidencia -->
    @if ($showVerifyModal && $selectedEvidence)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title font-weight-bold">
                            <i class="bi bi-patch-check text-success me-2"></i> Evaluación de Evidencia {{ $selectedEvidence->code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeVerifyModal"></button>
                    </div>

                    <form wire:submit.prevent="submitVerification">
                        <div class="modal-body">
                            <div class="alert alert-dark border border-secondary mb-3 py-2 px-3 small">
                                <div class="fw-bold text-white mb-1">{{ $selectedEvidence->name }}</div>
                                <div class="text-secondary">Proyecto: <span class="text-info">{{ $selectedEvidence->project->code }} - {{ $selectedEvidence->project->name }}</span></div>
                                <div class="text-secondary">Versión a evaluar: <span class="text-light fw-semibold">v{{ $selectedEvidence->currentVersion?->number ?? 1 }} ({{ $selectedEvidence->currentVersion?->file_original_name }})</span></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary fw-semibold">
                                    Decisión de Verificación <span class="text-danger">*</span>
                                </label>

                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check bg-dark border border-secondary rounded p-2 ps-4">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="verificationStatus"
                                               id="status_verificado"
                                               value="{{ \App\Models\EvidenceStatus::VERIFICADA }}"
                                               wire:model.live="verificationStatus">
                                        <label class="form-check-label text-success fw-bold ms-1" for="status_verificado">
                                            <i class="bi bi-check-circle me-1"></i> Verificado (Aprobar evidencia)
                                        </label>
                                    </div>

                                    <div class="form-check bg-dark border border-secondary rounded p-2 ps-4">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="verificationStatus"
                                               id="status_observado"
                                               value="{{ \App\Models\EvidenceStatus::OBSERVADA }}"
                                               wire:model.live="verificationStatus">
                                        <label class="form-check-label text-warning fw-bold ms-1" for="status_observado">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Observado (Requiere correcciones o aclaraciones)
                                        </label>
                                    </div>

                                    <div class="form-check bg-dark border border-secondary rounded p-2 ps-4">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="verificationStatus"
                                               id="status_rechazado"
                                               value="{{ \App\Models\EvidenceStatus::RECHAZADA }}"
                                               wire:model.live="verificationStatus">
                                        <label class="form-check-label text-danger fw-bold ms-1" for="status_rechazado">
                                            <i class="bi bi-x-circle me-1"></i> Rechazado (No válida o desaprobada)
                                        </label>
                                    </div>
                                </div>

                                @error('verificationStatus')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="verificationReason" class="form-label text-secondary fw-semibold">
                                    Motivo / Justificación
                                    @if (in_array((int) $verificationStatus, [\App\Models\EvidenceStatus::OBSERVADA, \App\Models\EvidenceStatus::RECHAZADA], true))
                                        <span class="text-danger">* (Obligatorio para Observar / Rechazar)</span>
                                    @else
                                        <small class="text-muted">(Opcional para Verificado)</small>
                                    @endif
                                </label>
                                <textarea wire:model="verificationReason"
                                          id="verificationReason"
                                          rows="4"
                                          class="form-control bg-dark text-white border-secondary @error('verificationReason') is-invalid @enderror"
                                          placeholder="{{ in_array((int) $verificationStatus, [\App\Models\EvidenceStatus::OBSERVADA, \App\Models\EvidenceStatus::RECHAZADA], true) ? 'Indique los motivos detallados de la observación o rechazo...' : 'Comentarios u observaciones opcionales...' }}"></textarea>
                                @error('verificationReason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeVerifyModal">Cancelar</button>
                            <button type="submit" class="btn btn-success fw-semibold d-flex align-items-center gap-2" wire:loading.attr="disabled">
                                <span wire:loading wire:target="submitVerification" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i wire:loading.remove wire:target="submitVerification" class="bi bi-check2-square"></i>
                                Guardar Evaluación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
