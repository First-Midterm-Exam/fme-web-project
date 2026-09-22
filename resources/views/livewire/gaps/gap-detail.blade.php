<div class="container-fluid py-4">
    {{-- Header & Breadcrumb --}}
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

    {{-- Feedback Messages --}}
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

    {{-- Alert if overdue (RF-30) --}}
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
        {{-- Left Column: Gap Details --}}
        <div class="col-lg-7">
            {{-- Gap Main Information Card --}}
            <div class="card bg-dark border-secondary shadow-sm mb-4">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-uppercase fw-semibold small text-secondary">
                        <i class="bi bi-info-circle me-1"></i>Información del Gap
                    </span>
                    <div class="d-flex gap-2 align-items-center">
                        {{-- Severity Badge --}}
                        <span class="badge {{ match($gap->severity) {
                            'Crítica' => 'bg-danger',
                            'Alta' => 'bg-warning text-dark',
                            'Media' => 'bg-info text-dark',
                            default => 'bg-secondary',
                        } }} px-2 py-1">
                            Severidad: {{ $gap->severity ?? 'Sin asignar' }}
                        </span>

                        {{-- Status Badge --}}
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

            {{-- Bitácora de Auditoría (RNF-06) --}}
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary">
                    <span class="text-uppercase fw-semibold small text-secondary">
                        <i class="bi bi-clock-history me-1"></i>Bitácora de Cambios (RNF-06)
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

        {{-- Right Column: Acciones Correctivas (HU-18) --}}
        <div class="col-lg-5">
            <div class="card bg-dark border-secondary shadow-sm mb-4">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <span class="text-uppercase fw-semibold small text-secondary">
                        <i class="bi bi-tools me-1"></i>Acciones Correctivas (HU-18)
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

                            {{-- Progreso de la acción --}}
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span>Avance de la acción</span>
                                    <span class="fw-bold text-light">{{ $activeAction->progress_percent }}%</span>
                                </div>
                                <div class="progress bg-black bg-opacity-50" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $activeAction->progress_percent }}%" aria-valuenow="{{ $activeAction->progress_percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
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

                    {{-- Historial de acciones correctivas previas (si hay más de una cerrada) --}}
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

    {{-- Modal: Crear Acción Correctiva (HU-18) --}}
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
</div>
