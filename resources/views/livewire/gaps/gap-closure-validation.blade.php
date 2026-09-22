<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 font-weight-bold text-white mb-1">Validación de Cierre de Gaps</h2>
            <p class="text-secondary mb-0">Revisión de soluciones y cierre formal de brechas en estado Resuelto.</p>
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
                <div class="col-md-5 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-secondary border-secondary border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               class="form-control bg-dark text-white border-secondary border-start-0"
                               placeholder="Buscar por código o título...">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select wire:model.live="severityFilter" class="form-select bg-dark text-white border-secondary">
                        <option value="all">— Todas las Severidades —</option>
                        <option value="Crítica">Crítica</option>
                        <option value="Alta">Alta</option>
                        <option value="Media">Media</option>
                        <option value="Baja">Baja</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-4">
                    <select wire:model.live="projectFilter" class="form-select bg-dark text-white border-secondary">
                        <option value="all">— Todos los Proyectos Accesibles —</option>
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
                            <th scope="col" style="width: 10%;">Código</th>
                            <th scope="col">Título & Práctica CMMI</th>
                            <th scope="col">Proyecto</th>
                            <th scope="col">Responsable Gap</th>
                            <th scope="col">Evidencia de Solución</th>
                            <th scope="col" class="text-center">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resolvedGaps as $gap)
                            @php
                                $activeAction = $gap->correctiveActions->first();
                                $solutionEvidence = $activeAction?->solutionEvidence;
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge bg-secondary font-monospace fs-6 px-2 py-1">
                                        {{ $gap->code }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-white">{{ $gap->title }}</div>
                                    <small class="text-secondary">
                                        <span class="badge bg-secondary font-monospace me-1">{{ $gap->practiceEvaluation?->practice?->code ?? 'N/A' }}</span>
                                        {{ Str::limit($gap->practiceEvaluation?->practice?->name, 35) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="text-info fw-semibold">{{ $gap->practiceEvaluation?->appraisal?->project?->code }}</span>
                                    <div class="small text-secondary">{{ Str::limit($gap->practiceEvaluation?->appraisal?->project?->name, 25) }}</div>
                                </td>
                                <td>
                                    <div class="text-white small">{{ $gap->assignedTo?->name ?? 'Sin asignar' }}</div>
                                    <div class="text-secondary small">{{ $gap->assignedTo?->email }}</div>
                                </td>
                                <td>
                                    @if ($solutionEvidence)
                                        <a href="{{ route('evidencias.download', $solutionEvidence) }}"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 py-1 px-2"
                                           title="Descargar evidencia de solución">
                                            <i class="bi bi-file-earmark-check"></i>
                                            <span class="small text-truncate" style="max-width: 140px;">{{ $solutionEvidence->file_original_name ?? $solutionEvidence->name }}</span>
                                        </a>
                                    @else
                                        <span class="text-muted small fst-italic">Sin evidencia adjunta</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark fw-bold">
                                        {{ $gap->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button wire:click="openDetailModal({{ $gap->id }})"
                                                class="btn btn-outline-light"
                                                title="Ver detalle completo del gap y su bitácora">
                                            <i class="bi bi-eye me-1"></i> Detalle
                                        </button>
                                        @can('validate', $gap)
                                            <button wire:click="openValidationModal({{ $gap->id }})"
                                                    class="btn btn-success fw-semibold"
                                                    title="Validar el cierre o rechazar la solución de este gap">
                                                <i class="bi bi-patch-check me-1"></i> Validar
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">
                                    <i class="bi bi-check2-circle fs-2 d-block mb-2 text-muted"></i>
                                    No hay gaps en estado Resuelto pendientes de validación de cierre.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $resolvedGaps->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Detalle del Gap -->
    @if ($showDetailModal && $selectedGap)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title font-weight-bold">
                            <i class="bi bi-info-circle text-info me-2"></i> Detalle del Gap {{ $selectedGap->code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDetailModal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="text-secondary small fw-semibold d-block">Título</label>
                                <span class="fs-5 fw-bold text-white">{{ $selectedGap->title }}</span>
                            </div>
                            <div class="col-md-4">
                                <label class="text-secondary small fw-semibold d-block">Estado / Severidad</label>
                                <span class="badge bg-info text-dark fs-6 me-1">{{ $selectedGap->status }}</span>
                                <span class="badge bg-secondary fs-6">{{ $selectedGap->severity }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-secondary small fw-semibold d-block">Descripción</label>
                            <div class="bg-dark border border-secondary rounded p-2 text-light small">
                                {{ $selectedGap->description ?: 'Sin descripción registrada.' }}
                            </div>
                        </div>

                        @php
                            $action = $selectedGap->correctiveActions->first();
                            $evidence = $action?->solutionEvidence;
                        @endphp

                        @if ($action)
                            <div class="card bg-dark border-secondary p-3 mb-3">
                                <h6 class="fw-bold text-white mb-2"><i class="bi bi-tools text-primary me-1"></i>Acción Correctiva Asociada</h6>
                                <p class="small text-light mb-2">{{ $action->description }}</p>
                                <div class="row g-2 small text-secondary">
                                    <div class="col-6">Responsable: <strong class="text-white">{{ $action->responsible?->name }}</strong></div>
                                    <div class="col-6">Avance actual: <strong class="text-info">{{ $action->progress_percent }}%</strong></div>
                                </div>
                                @if ($evidence)
                                    <div class="mt-2 pt-2 border-top border-secondary d-flex justify-content-between align-items-center">
                                        <span class="small text-light"><i class="bi bi-paperclip me-1"></i>Evidencia de Solución: <strong>{{ $evidence->name }}</strong></span>
                                        <a href="{{ route('evidencias.download', $evidence) }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>Descargar</a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="mb-3">
                            <h6 class="fw-bold text-white mb-2"><i class="bi bi-clock-history me-1"></i>Bitácora del Gap</h6>
                            <div class="bg-dark border border-secondary rounded p-2 small style-scroll" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($selectedGap->logs as $log)
                                    <div class="mb-2 pb-2 border-bottom border-secondary border-opacity-50">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-light">{{ $log->user?->name ?? 'Sistema' }}</strong>
                                            <span class="text-secondary font-monospace" style="font-size: 0.75rem;">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="text-info small">{{ $log->description }}</div>
                                    </div>
                                @empty
                                    <span class="text-muted">Sin registros de bitácora.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closeDetailModal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Validar Cierre del Gap -->
    @if ($showValidationModal && $selectedGap)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title font-weight-bold">
                            <i class="bi bi-patch-check text-success me-2"></i> Validación de Cierre de Gap {{ $selectedGap->code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeValidationModal"></button>
                    </div>

                    <form wire:submit.prevent="submitValidation">
                        <div class="modal-body">
                            @php
                                $action = $selectedGap->correctiveActions->first();
                                $evidence = $action?->solutionEvidence;
                            @endphp

                            <div class="alert alert-dark border border-secondary mb-3 py-2 px-3 small">
                                <div class="fw-bold text-white mb-1">{{ $selectedGap->title }}</div>
                                <div class="text-secondary">Proyecto: <span class="text-info">{{ $selectedGap->practiceEvaluation?->appraisal?->project?->code }} - {{ $selectedGap->practiceEvaluation?->appraisal?->project?->name }}</span></div>
                                @if ($evidence)
                                    <div class="text-secondary mt-1">
                                        Evidencia adjunta:
                                        <a href="{{ route('evidencias.download', $evidence) }}" target="_blank" class="text-success fw-bold ms-1 text-decoration-underline">
                                            <i class="bi bi-download me-1"></i>{{ $evidence->file_original_name ?? $evidence->name }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary fw-semibold">
                                    Decisión de Validación <span class="text-danger">*</span>
                                </label>

                                <div class="d-flex flex-column gap-2">
                                    <div class="form-check bg-dark border border-secondary rounded p-2 ps-4">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="decision"
                                               id="decision_approve"
                                               value="approve"
                                               wire:model.live="decision">
                                        <label class="form-check-label text-success fw-bold ms-1" for="decision_approve">
                                            <i class="bi bi-check-circle me-1"></i> Aprobar Cierre (Cerrar Gap y Acción Correctiva)
                                        </label>
                                    </div>

                                    <div class="form-check bg-dark border border-secondary rounded p-2 ps-4">
                                        <input class="form-check-input"
                                               type="radio"
                                               name="decision"
                                               id="decision_reject"
                                               value="reject"
                                               wire:model.live="decision">
                                        <label class="form-check-label text-warning fw-bold ms-1" for="decision_reject">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Rechazar Solución (Devolver Gap a En progreso)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @if ($decision === 'reject')
                                <div class="mb-3">
                                    <label for="rejectionReason" class="form-label text-secondary fw-semibold">
                                        Motivo de Rechazo <span class="text-danger">* (Obligatorio)</span>
                                    </label>
                                    <textarea wire:model="rejectionReason"
                                              id="rejectionReason"
                                              rows="4"
                                              class="form-control bg-dark text-white border-secondary @error('rejectionReason') is-invalid @enderror"
                                              placeholder="Detalle las razones del rechazo para que el responsable realice las correcciones..."></textarea>
                                    @error('rejectionReason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeValidationModal">Cancelar</button>
                            <button type="submit" class="btn btn-success fw-semibold d-flex align-items-center gap-2" wire:loading.attr="disabled">
                                <span wire:loading wire:target="submitValidation" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i wire:loading.remove wire:target="submitValidation" class="bi bi-check2-square"></i>
                                Guardar Validación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
