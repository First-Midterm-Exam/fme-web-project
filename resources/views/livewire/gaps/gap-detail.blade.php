<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-secondary text-decoration-none">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('gaps.index') }}" class="text-secondary text-decoration-none">Gaps</a></li>
                    <li class="breadcrumb-item active text-light" aria-current="page">{{ $gap->code }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h1 class="h3 mb-0 fw-bold text-white">
                    <span class="badge bg-secondary font-monospace me-2">{{ $gap->code }}</span>
                    {{ $gap->title }}
                </h1>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('gaps.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Gaps
            </a>

            @can('create', [App\Models\CorrectiveAction::class, $gap])
                @if (! $gap->hasActiveCorrectiveAction())
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" wire:click="openCreateModal">
                        <i class="bi bi-plus-circle me-1"></i>Crear acción correctiva
                    </button>
                @else
                    <span class="badge bg-info text-dark py-2 px-3">
                        <i class="bi bi-gear-wide-connected me-1"></i>Acción correctiva activa
                    </span>
                @endif
            @endcan
        </div>
    </div>

    @if ($successMessage)
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ $successMessage }}
            <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errorMessage }}
            <button type="button" class="btn-close" wire:click="$set('errorMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    @if ($gap->isOverdue())
        <div class="alert alert-danger d-flex align-items-center mb-4 border-danger shadow-sm py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger"></i>
            <div>
                <strong class="d-block">Alerta de Gap Vencido (RF-30)</strong>
                <span class="small">Este gap superó su fecha límite el <strong>{{ \Carbon\Carbon::parse($gap->due_date)->format('d/m/Y') }}</strong> y aún permanece sin resolver.</span>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card bg-dark border-secondary shadow-sm mb-4">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-uppercase fw-semibold small text-secondary">
                        <i class="bi bi-info-circle me-1"></i>Información del Gap
                    </span>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge {{ match($gap->severity) {
                            'Crítica' => 'bg-danger',
                            'Alta' => 'bg-warning text-dark',
                            'Media' => 'bg-info text-dark',
                            default => 'bg-secondary',
                        } }} px-2 py-1">
                            Severidad: {{ $gap->severity ?? 'Sin asignar' }}
                        </span>

                        <span class="badge {{ match($gap->status) {
                            'Abierto' => 'bg-warning text-dark',
                            'En progreso' => 'bg-primary',
                            'Resuelto' => 'bg-info text-dark',
                            'Verificado' => 'bg-success',
                            default => 'bg-secondary',
                        } }} px-2 py-1">
                            Estado: {{ $gap->status }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <h5 class="text-white fw-bold mb-3">{{ $gap->title }}</h5>

                    <div class="mb-4">
                        <label class="text-secondary small text-uppercase fw-semibold d-block mb-1">Descripción</label>
                        <p class="text-light bg-black bg-opacity-25 p-3 rounded border border-secondary mb-0">
                            {{ $gap->description ?: 'Sin descripción adicional registrada.' }}
                        </p>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-secondary small text-uppercase fw-semibold d-block">Proyecto</label>
                            <span class="text-white fw-medium">
                                <i class="bi bi-folder2-open text-primary me-1"></i>
                                {{ $gap->practiceEvaluation?->appraisal?->project?->name ?? 'N/A' }}
                            </span>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-secondary small text-uppercase fw-semibold d-block">Appraisal</label>
                            <span class="text-white fw-medium">
                                <i class="bi bi-clipboard-check text-info me-1"></i>
                                {{ $gap->practiceEvaluation?->appraisal?->name ?? 'N/A' }}
                            </span>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-secondary small text-uppercase fw-semibold d-block">Práctica CMMI</label>
                            <span class="text-white">
                                <span class="badge bg-secondary font-monospace me-1">{{ $gap->practiceEvaluation?->practice?->code ?? 'N/A' }}</span>
                                {{ $gap->practiceEvaluation?->practice?->name ?? '' }}
                            </span>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-secondary small text-uppercase fw-semibold d-block">Criterio Específico</label>
                            <span class="text-white">
                                @if ($gap->practiceCriterion)
                                    <span class="badge bg-secondary font-monospace me-1">{{ $gap->practiceCriterion->code }}</span>
                                    {{ Str::limit($gap->practiceCriterion->description, 45) }}
                                @else
                                    <span class="text-secondary">Evaluación general de práctica</span>
                                @endif
                            </span>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-secondary small text-uppercase fw-semibold d-block">Responsable del Gap</label>
                            <span class="text-white">
                                <i class="bi bi-person text-secondary me-1"></i>
                                {{ $gap->assignedTo?->name ?? 'Sin asignar' }}
                            </span>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-secondary small text-uppercase fw-semibold d-block">Fecha Límite</label>
                            <span class="{{ $gap->isOverdue() ? 'text-danger fw-bold' : 'text-white' }}">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $gap->due_date ? \Carbon\Carbon::parse($gap->due_date)->format('d/m/Y') : 'Sin fecha definida' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary">
                    <span class="text-uppercase fw-semibold small text-secondary">
                        <i class="bi bi-clock-history me-1"></i>Bitácora de Cambios del Gap
                    </span>
                </div>
                <div class="card-body p-3">
                    @if ($gap->logs->isEmpty())
                        <p class="text-secondary small mb-0 text-center py-3">No hay registros previos en la bitácora.</p>
                    @else
                        <div class="timeline">
                            @foreach ($gap->logs as $log)
                                <div class="d-flex gap-3 mb-3 pb-3 border-bottom border-secondary border-opacity-50">
                                    <div class="text-primary mt-1">
                                        <i class="bi bi-dot fs-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-light small">{{ $log->user?->name ?? 'Sistema' }}</strong>
                                            <span class="text-secondary small font-monospace">{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="text-light small mt-1">
                                            <span class="badge bg-secondary font-monospace me-1">{{ $log->field }}</span>
                                            @if ($log->old_value)
                                                <span class="text-secondary text-decoration-line-through">{{ $log->old_value }}</span>
                                                <i class="bi bi-arrow-right text-secondary mx-1"></i>
                                            @endif
                                            <span class="text-info fw-semibold">{{ $log->new_value }}</span>
                                        </div>
                                        @if ($log->description)
                                            <div class="text-secondary small mt-1 fst-italic">{{ $log->description }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card bg-dark border-secondary shadow-sm mb-4">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-uppercase fw-semibold small text-secondary">
                        <i class="bi bi-tools me-1"></i>Acción Correctiva Activa
                    </span>
                    <span class="badge bg-secondary">{{ $gap->correctiveActions->count() }}</span>
                </div>

                <div class="card-body">
                    @if ($activeAction)
                        <div class="p-3 rounded border border-primary bg-primary bg-opacity-10 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary text-uppercase">Acción Activa</span>
                                <span class="text-secondary small font-monospace">Creada el {{ $activeAction->created_at->format('d/m/Y') }}</span>
                            </div>

                            <h6 class="text-white fw-bold mb-2">{{ $activeAction->description }}</h6>

                            <div class="row g-2 small mt-2">
                                <div class="col-6">
                                    <span class="text-secondary d-block">Responsable:</span>
                                    <strong class="text-light">
                                        <i class="bi bi-person-badge text-info me-1"></i>
                                        {{ $activeAction->responsible?->name ?? 'Sin asignar' }}
                                    </strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-secondary d-block">Fecha Límite:</span>
                                    <strong class="{{ $activeAction->isOverdue() ? 'text-danger' : 'text-light' }}">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ \Carbon\Carbon::parse($activeAction->due_date)->format('d/m/Y') }}
                                    </strong>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span>Avance de la acción</span>
                                    <span class="fw-bold text-light">{{ $activeAction->progress_percent }}%</span>
                                </div>
                                <div class="progress bg-black bg-opacity-50" style="height: 10px;">
                                    <div class="progress-bar {{ $activeAction->progress_percent == 100 ? 'bg-success' : 'bg-info' }}"
                                         role="progressbar"
                                         style="width: {{ $activeAction->progress_percent }}%"
                                         aria-valuenow="{{ $activeAction->progress_percent }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>

                            @if ($activeAction->solutionEvidence)
                                <div class="mt-3 p-2 bg-dark rounded border border-success border-opacity-50 small d-flex justify-content-between align-items-center">
                                    <div class="text-truncate me-2">
                                        <i class="bi bi-file-earmark-check text-success me-1"></i>
                                        <span class="text-light fw-semibold">Evidencia de Solución:</span>
                                        <span class="text-secondary d-block text-truncate" title="{{ $activeAction->solutionEvidence->name }}">
                                            {{ $activeAction->solutionEvidence->name }}
                                        </span>
                                    </div>
                                    <a href="{{ route('evidencias.download', $activeAction->solutionEvidence) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-success text-nowrap">
                                        <i class="bi bi-download me-1"></i>Ver
                                    </a>
                                </div>
                            @endif

                            @can('update', $activeAction)
                                <button type="button"
                                        class="btn btn-outline-info btn-sm mt-3 w-100 fw-semibold"
                                        wire:click="openUpdateProgressModal">
                                    <i class="bi bi-speedometer2 me-1"></i>Actualizar Avance y Evidencia
                                </button>
                            @endcan
                        </div>

                        <div class="mt-4">
                            <span class="text-uppercase fw-semibold small text-secondary d-block mb-2">
                                <i class="bi bi-journal-text me-1"></i>Historial de Avance de la Acción
                            </span>

                            @if ($activeAction->logs->isEmpty())
                                <p class="text-secondary small mb-0 fst-italic">Aún no hay actualizaciones de avance registradas.</p>
                            @else
                                <div class="bg-black bg-opacity-25 rounded border border-secondary p-2 style-scroll" style="max-height: 220px; overflow-y: auto;">
                                    @foreach ($activeAction->logs as $actLog)
                                        <div class="border-bottom border-secondary border-opacity-50 pb-2 mb-2 last-border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-light small">{{ $actLog->user?->name ?? 'Usuario' }}</strong>
                                                <span class="text-secondary font-monospace" style="font-size: 0.75rem;">
                                                    {{ $actLog->created_at->format('d/m/Y H:i') }}
                                                </span>
                                            </div>
                                            <div class="small mt-1">
                                                <span class="badge bg-secondary font-monospace">{{ $actLog->field }}</span>
                                                @if ($actLog->old_value !== null)
                                                    <span class="text-secondary text-decoration-line-through">{{ $actLog->old_value }}%</span>
                                                    <i class="bi bi-arrow-right text-secondary mx-1"></i>
                                                @endif
                                                <span class="text-info fw-bold">{{ $actLog->new_value }}%</span>
                                            </div>
                                            @if ($actLog->description)
                                                <div class="text-secondary small mt-1 fst-italic">{{ $actLog->description }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-shield-slash text-secondary display-5 d-block mb-3"></i>
                            <h6 class="text-white fw-semibold mb-1">Sin Acción Correctiva Activa</h6>
                            <p class="text-secondary small mb-3">
                                Este gap no tiene ninguna acción correctiva en curso para su resolución formal.
                            </p>

                            @can('create', [App\Models\CorrectiveAction::class, $gap])
                                <button type="button" class="btn btn-primary btn-sm" wire:click="openCreateModal">
                                    <i class="bi bi-plus-circle me-1"></i>Crear acción correctiva ahora
                                </button>
                            @endcan
                        </div>
                    @endif

                    @php
                        $closedActions = $gap->correctiveActions->filter(fn ($a) => ! $a->isActive());
                    @endphp

                    @if ($closedActions->isNotEmpty())
                        <hr class="border-secondary my-3">
                        <span class="text-uppercase fw-semibold small text-secondary d-block mb-2">Acciones Anteriores Cerradas</span>
                        @foreach ($closedActions as $closed)
                            <div class="p-2 mb-2 rounded bg-black bg-opacity-25 border border-secondary small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-white fw-medium">{{ Str::limit($closed->description, 50) }}</span>
                                    <span class="badge bg-secondary">Cerrada</span>
                                </div>
                                <div class="text-secondary mt-1">
                                    Responsable: {{ $closed->responsible?->name }} | Fecha: {{ \Carbon\Carbon::parse($closed->due_date)->format('d/m/Y') }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($showingCreateModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark border-secondary shadow-lg">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-white fw-bold">
                            <i class="bi bi-tools text-primary me-2"></i>Crear Acción Correctiva
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeCreateModal" aria-label="Close"></button>
                    </div>

                    <form wire:submit.prevent="createCorrectiveAction">
                        <div class="modal-body text-light">
                            @if ($errors->has('gap'))
                                <div class="alert alert-danger py-2 small mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first('gap') }}
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-secondary fw-semibold">Gap Asociado</label>
                                <input type="text" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" value="{{ $gap->code }} — {{ $gap->title }}" disabled>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label small text-uppercase text-secondary fw-semibold">
                                    Descripción de la Acción <span class="text-danger">*</span>
                                </label>
                                <textarea id="description" rows="3" class="form-control bg-secondary bg-opacity-25 border-secondary text-white @error('description') is-invalid @enderror" wire:model="description" placeholder="Describe claramente qué se realizará para subsanar el gap..."></textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="responsible_id" class="form-label small text-uppercase text-secondary fw-semibold">
                                    Responsable Asignado <span class="text-danger">*</span>
                                </label>
                                <select id="responsible_id" class="form-select bg-secondary bg-opacity-25 border-secondary text-white @error('responsible_id') is-invalid @enderror" wire:model="responsible_id">
                                    <option value="">-- Seleccionar Responsable --</option>
                                    @foreach ($availableUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                <small class="text-secondary d-block mt-1">Normalmente es un Jefe de Proyecto o Colaborador del proyecto afectado.</small>
                                @error('responsible_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="due_date" class="form-label small text-uppercase text-secondary fw-semibold">
                                    Fecha Límite <span class="text-danger">*</span>
                                </label>
                                <input id="due_date" type="date" class="form-control bg-secondary bg-opacity-25 border-secondary text-white @error('due_date') is-invalid @enderror" wire:model="due_date">
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase text-secondary fw-semibold">Porcentaje de Avance Inicial</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" value="0%" disabled>
                                    <span class="input-group-text bg-secondary border-secondary text-secondary">Inicial</span>
                                </div>
                                <small class="text-secondary d-block mt-1">El avance inicial comienza obligatoriamente en 0%.</small>
                            </div>

                            <div class="alert alert-info py-2 small mb-0">
                                <i class="bi bi-info-circle me-1"></i>Al crear la acción correctiva, el gap cambiará automáticamente de <strong>"Abierto"</strong> a <strong>"En progreso"</strong>.
                            </div>
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeCreateModal">Cancelar</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-check2-circle me-1"></i>Crear Acción Correctiva
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showingUpdateProgressModal && $activeAction)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark border-secondary shadow-lg text-white">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title font-weight-bold">
                            <i class="bi bi-speedometer2 text-info me-2"></i>Actualizar Avance de Acción Correctiva
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeUpdateProgressModal" aria-label="Close"></button>
                    </div>

                    <form wire:submit.prevent="updateProgress">
                        <div class="modal-body">
                            @if ($errors->has('gap'))
                                <div class="alert alert-danger py-2 small mb-3">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $errors->first('gap') }}
                                </div>
                            @endif

                            <div class="alert alert-dark border border-secondary py-2 px-3 small mb-3">
                                <strong class="d-block text-white">{{ $activeAction->description }}</strong>
                                <span class="text-secondary">Responsable: {{ $activeAction->responsible?->name }} | Límite: {{ \Carbon\Carbon::parse($activeAction->due_date)->format('d/m/Y') }}</span>
                            </div>

                            <div class="mb-4">
                                <label for="progress_percent" class="form-label small text-uppercase text-secondary fw-semibold d-flex justify-content-between">
                                    <span>Porcentaje de Avance <span class="text-danger">*</span></span>
                                    <span class="text-info font-monospace fs-6 fw-bold">{{ $progress_percent }}%</span>
                                </label>
                                <input type="range"
                                       id="progress_percent"
                                       min="0"
                                       max="100"
                                       step="5"
                                       class="form-range"
                                       wire:model.live="progress_percent">
                                <div class="d-flex justify-content-between text-secondary font-monospace" style="font-size: 0.75rem;">
                                    <span>0% (Inicial)</span>
                                    <span>50% (En curso)</span>
                                    <span>100% (Completado)</span>
                                </div>
                                @error('progress_percent')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="solution_file" class="form-label small text-uppercase text-secondary fw-semibold">
                                    Evidencia de Solución
                                    @if ((int) $progress_percent === 100 && ! $activeAction->solution_evidence_id)
                                        <span class="text-danger">* (Obligatorio para llegar a 100%)</span>
                                    @else
                                        <small class="text-muted">(Opcional / Requerido al 100%)</small>
                                    @endif
                                </label>

                                @if ($activeAction->solutionEvidence)
                                    <div class="mb-2 p-2 bg-dark rounded border border-success small">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                                        Archivada actualmente: <strong>{{ $activeAction->solutionEvidence->name }}</strong>
                                        <small class="text-secondary d-block">Subir un nuevo archivo reemplazará la evidencia de solución actual.</small>
                                    </div>
                                @endif

                                <input type="file"
                                       id="solution_file"
                                       class="form-control bg-secondary bg-opacity-25 border-secondary text-white @error('file') is-invalid @enderror @error('solution_file') is-invalid @enderror"
                                       wire:model="solution_file">

                                <div wire:loading wire:target="solution_file" class="text-info small mt-1">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Cargando archivo temporal...
                                </div>

                                @error('file')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @error('solution_file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="progress_comment" class="form-label small text-uppercase text-secondary fw-semibold">
                                    Comentario / Justificación de la Actualización
                                </label>
                                <textarea id="progress_comment"
                                          rows="3"
                                          class="form-control bg-secondary bg-opacity-25 border-secondary text-white @error('progress_comment') is-invalid @enderror"
                                          wire:model="progress_comment"
                                          placeholder="Detalla las actividades realizadas en esta actualización..."></textarea>
                                @error('progress_comment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ((int) $progress_percent === 100)
                                <div class="alert alert-info py-2 small mb-0">
                                    <i class="bi bi-info-circle-fill me-1"></i>Al completar la acción al 100% con evidencia adjunta, el Gap padre pasará automáticamente a estado <strong>"Resuelto"</strong>.
                                </div>
                            @endif
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeUpdateProgressModal">Cancelar</button>
                            <button type="submit" class="btn btn-info btn-sm text-dark fw-bold d-flex align-items-center gap-1" wire:loading.attr="disabled">
                                <span wire:loading wire:target="updateProgress" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i wire:loading.remove wire:target="updateProgress" class="bi bi-check2-square"></i>
                                Guardar Avance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
