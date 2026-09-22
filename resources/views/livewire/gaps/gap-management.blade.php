<div class="container-fluid py-4">
    {{-- Header & Breadcrumbs --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-secondary text-decoration-none">Inicio</a></li>
                    @if ($appraisal)
                        <li class="breadcrumb-item"><a href="{{ route('appraisals.index') }}" class="text-secondary text-decoration-none">Appraisals</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('proyectos.show', $appraisal->project_id) }}" class="text-secondary text-decoration-none">{{ $appraisal->project->name }}</a></li>
                        <li class="breadcrumb-item active text-light" aria-current="page">Gaps de {{ $appraisal->name }}</li>
                    @else
                        <li class="breadcrumb-item active text-light" aria-current="page">Gaps y Brechas</li>
                    @endif
                </ol>
            </nav>
            <h1 class="h3 mb-0 fw-bold text-white">
                <i class="bi bi-exclamation-octagon text-danger me-2"></i>Gestión de Gaps
                @if ($appraisal)
                    <span class="fs-6 fw-normal text-secondary ms-2">— {{ $appraisal->name }}</span>
                @endif
            </h1>
        </div>

        @if ($appraisal)
            <div>
                <a href="{{ route('appraisals.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Volver a Appraisals
                </a>
            </div>
        @endif
    </div>

    {{-- Feedback Messages --}}
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Resumen de Gaps (KPI Cards) --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-4 col-6">
            <div class="card bg-dark border-secondary shadow-sm text-center py-2 h-100">
                <div class="card-body p-2">
                    <div class="text-secondary small text-uppercase fw-semibold">Total Gaps</div>
                    <div class="h3 mb-0 fw-bold text-white">{{ $counts['total'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-6">
            <div class="card bg-dark border-secondary shadow-sm text-center py-2 h-100">
                <div class="card-body p-2">
                    <div class="text-warning small text-uppercase fw-semibold">Abiertos</div>
                    <div class="h3 mb-0 fw-bold text-warning">{{ $counts['abiertos'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-6">
            <div class="card bg-dark border-secondary shadow-sm text-center py-2 h-100">
                <div class="card-body p-2">
                    <div class="text-primary small text-uppercase fw-semibold">En Progreso</div>
                    <div class="h3 mb-0 fw-bold text-primary">{{ $counts['en_progreso'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-6">
            <div class="card bg-dark border-secondary shadow-sm text-center py-2 h-100">
                <div class="card-body p-2">
                    <div class="text-info small text-uppercase fw-semibold">Resueltos</div>
                    <div class="h3 mb-0 fw-bold text-info">{{ $counts['resueltos'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-6">
            <div class="card bg-dark border-secondary shadow-sm text-center py-2 h-100">
                <div class="card-body p-2">
                    <div class="text-success small text-uppercase fw-semibold">Verificados</div>
                    <div class="h3 mb-0 fw-bold text-success">{{ $counts['verificados'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-2 col-sm-4 col-6">
            <div class="card bg-dark border-danger shadow-sm text-center py-2 h-100 {{ $counts['vencidos'] > 0 ? 'bg-danger bg-opacity-10' : '' }}">
                <div class="card-body p-2">
                    <div class="text-danger small text-uppercase fw-semibold">
                        <i class="bi bi-alarm-fill me-1"></i>Vencidos
                    </div>
                    <div class="h3 mb-0 fw-bold text-danger">{{ $counts['vencidos'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de Filtros y Búsqueda --}}
    <div class="card bg-dark border-secondary shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                {{-- Búsqueda --}}
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
                        <input type="text"
                               class="form-control bg-dark text-white border-secondary"
                               placeholder="Buscar por código, título o práctica..."
                               wire:model.live.debounce.300ms="search">
                    </div>
                </div>

                {{-- Filtro por Severidad --}}
                <div class="col-lg-3 col-md-3 col-6">
                    <select class="form-select bg-dark text-white border-secondary" wire:model.live="severityFilter">
                        <option value="all">Todas las severidades</option>
                        <option value="{{ App\Models\Gap::SEVERITY_CRITICA }}">Crítica</option>
                        <option value="{{ App\Models\Gap::SEVERITY_ALTA }}">Alta</option>
                        <option value="{{ App\Models\Gap::SEVERITY_MEDIA }}">Media</option>
                        <option value="{{ App\Models\Gap::SEVERITY_BAJA }}">Baja</option>
                    </select>
                </div>

                {{-- Filtro por Estado --}}
                <div class="col-lg-3 col-md-3 col-6">
                    <select class="form-select bg-dark text-white border-secondary" wire:model.live="statusFilter">
                        <option value="all">Todos los estados</option>
                        <option value="{{ App\Models\Gap::STATUS_ABIERTO }}">Abierto</option>
                        <option value="{{ App\Models\Gap::STATUS_EN_PROGRESO }}">En progreso</option>
                        <option value="{{ App\Models\Gap::STATUS_RESUELTO }}">Resuelto</option>
                        <option value="{{ App\Models\Gap::STATUS_VERIFICADO }}">Verificado</option>
                    </select>
                </div>

                {{-- Toggle de Vencidos --}}
                <div class="col-lg-2 col-md-12 text-lg-end">
                    <button type="button"
                            wire:click="$toggle('overdueOnly')"
                            class="btn btn-sm w-100 {{ $overdueOnly ? 'btn-danger' : 'btn-outline-danger' }}">
                        <i class="bi bi-clock-history me-1"></i>{{ $overdueOnly ? 'Ver Todos' : 'Solo Vencidos' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Gaps --}}
    <div class="card bg-dark border-secondary shadow-sm">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0 border-secondary">
                <thead>
                    <tr class="table-secondary text-white">
                        <th scope="col" style="width: 110px;">Código</th>
                        <th scope="col">Título & Contexto</th>
                        <th scope="col" style="width: 120px;">Severidad</th>
                        <th scope="col" style="width: 160px;">Responsable</th>
                        <th scope="col" style="width: 140px;">Fecha Límite</th>
                        <th scope="col" style="width: 130px;">Estado</th>
                        <th scope="col" class="text-end" style="width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gaps as $gap)
                        @php
                            $isOverdue = $gap->isOverdue();
                        @endphp
                        <tr class="{{ $isOverdue ? 'table-danger bg-danger bg-opacity-10 border-start border-danger border-4' : '' }}" wire:key="gap-{{ $gap->id }}">
                            {{-- Código --}}
                            <td>
                                <span class="badge bg-dark border border-secondary text-light font-monospace fw-bold">
                                    {{ $gap->code }}
                                </span>
                            </td>

                            {{-- Título y Contexto --}}
                            <td>
                                <div class="fw-semibold text-white mb-1">{{ $gap->title }}</div>
                                <div class="small text-secondary d-flex flex-wrap gap-2 align-items-center">
                                    @if ($gap->practiceEvaluation)
                                        <span>
                                            <i class="bi bi-diagram-3 me-1 text-info"></i>
                                            <span class="text-light">{{ $gap->practiceEvaluation->practice->code ?? '' }}</span>
                                        </span>
                                        <span class="text-muted">•</span>
                                        <span>
                                            <i class="bi bi-folder2 me-1 text-primary"></i>
                                            {{ $gap->practiceEvaluation->appraisal->project->name ?? 'Proyecto' }}
                                        </span>
                                    @endif
                                    @if ($gap->practiceCriterion)
                                        <span class="text-muted">•</span>
                                        <span class="text-warning-emphasis">
                                            Criterio: {{ $gap->practiceCriterion->code }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Severidad --}}
                            <td>
                                <span class="badge {{ $gap->severityBadgeColor() }} px-2 py-1">
                                    {{ $gap->severity }}
                                </span>
                            </td>

                            {{-- Responsable --}}
                            <td>
                                @if ($gap->assignedTo)
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-circle text-info me-2"></i>
                                        <span class="text-light small">{{ $gap->assignedTo->name }}</span>
                                    </div>
                                @else
                                    <span class="text-secondary fst-italic small">
                                        <i class="bi bi-dash-circle me-1"></i>Sin asignar
                                    </span>
                                @endif
                            </td>

                            {{-- Fecha Límite & Alerta Vencido (RF-30) --}}
                            <td>
                                @if ($gap->due_date)
                                    <div class="{{ $isOverdue ? 'text-danger fw-bold' : 'text-light' }} small">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        {{ $gap->due_date->format('d/m/Y') }}
                                    </div>
                                    @if ($isOverdue)
                                        <span class="badge bg-danger text-white mt-1 gap-vencido-badge" id="gap-overdue-{{ $gap->id }}">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>¡Vencido!
                                        </span>
                                    @endif
                                @else
                                    <span class="text-secondary small fst-italic">Sin fecha</span>
                                @endif
                            </td>

                            {{-- Estado --}}
                            <td>
                                <span class="badge {{ $gap->statusBadgeColor() }} px-2 py-1">
                                    {{ $gap->status }}
                                </span>
                            </td>

                            {{-- Acciones --}}
                            <td class="text-end">
                                {{-- Ver Detalle y Acciones Correctivas (HU-18) --}}
                                <a href="{{ route('gaps.show', $gap) }}"
                                   class="btn btn-sm btn-outline-primary me-1"
                                   title="Ver detalle y acciones correctivas"
                                   wire:navigate>
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Ver Bitácora rápida --}}
                                <button type="button"
                                        wire:click="openDetail({{ $gap->id }})"
                                        class="btn btn-sm btn-outline-info me-1"
                                        title="Ver bitácora rápida">
                                    <i class="bi bi-clock-history"></i>
                                </button>

                                {{-- Editar (solo Gestor y Admin) --}}
                                @can('update', $gap)
                                    <button type="button"
                                            wire:click="openEdit({{ $gap->id }})"
                                            class="btn btn-sm btn-outline-warning btn-edit-gap"
                                            id="btn-edit-gap-{{ $gap->id }}"
                                            title="Editar gap">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-check2-circle fs-1 text-success d-block mb-2"></i>
                                <p class="mb-0">No se encontraron gaps con los filtros seleccionados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($gaps->hasPages())
            <div class="card-footer bg-dark border-secondary py-3">
                {{ $gaps->links() }}
            </div>
        @endif
    </div>

    {{-- =========================================================================
         Modal de Edición de Gap
         ========================================================================= --}}
    @if ($showEditModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-pencil-square text-warning me-2"></i>Editar Gap: {{ $code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeEditModal"></button>
                    </div>

                    <form wire:submit="save">
                        <div class="modal-body">
                            {{-- Título --}}
                            <div class="mb-3">
                                <label class="form-label text-secondary small">Título del Gap <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control bg-dark text-white border-secondary @error('title') is-invalid @enderror"
                                       wire:model="title">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                {{-- Severidad --}}
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small">Severidad <span class="text-danger">*</span></label>
                                    <select class="form-select bg-dark text-white border-secondary @error('severity') is-invalid @enderror"
                                            wire:model="severity">
                                        <option value="{{ App\Models\Gap::SEVERITY_CRITICA }}">Crítica</option>
                                        <option value="{{ App\Models\Gap::SEVERITY_ALTA }}">Alta</option>
                                        <option value="{{ App\Models\Gap::SEVERITY_MEDIA }}">Media</option>
                                        <option value="{{ App\Models\Gap::SEVERITY_BAJA }}">Baja</option>
                                    </select>
                                    @error('severity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Responsable --}}
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small">Responsable Asignado</label>
                                    <select class="form-select bg-dark text-white border-secondary @error('assignedToId') is-invalid @enderror"
                                            wire:model="assignedToId">
                                        <option value="">— Sin asignar —</option>
                                        @foreach ($availableUsers as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->getRoleNames()->first() ?? 'Usuario' }})</option>
                                        @endforeach
                                    </select>
                                    @error('assignedToId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Fecha Límite --}}
                                <div class="col-md-4">
                                    <label class="form-label text-secondary small">Fecha Límite</label>
                                    <input type="date"
                                           class="form-control bg-dark text-white border-secondary @error('dueDate') is-invalid @enderror"
                                           wire:model="dueDate">
                                    @error('dueDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Estado & Ciclo de Vida (RF-29) --}}
                            <div class="mb-3">
                                <label class="form-label text-secondary small">
                                    Estado del Gap (Ciclo de Vida: Abierto → En progreso → Resuelto → Verificado) <span class="text-danger">*</span>
                                </label>
                                <select class="form-select bg-dark text-white border-secondary @error('status') is-invalid @enderror"
                                        wire:model="status">
                                    <option value="{{ App\Models\Gap::STATUS_ABIERTO }}">Abierto</option>
                                    <option value="{{ App\Models\Gap::STATUS_EN_PROGRESO }}">En progreso</option>
                                    <option value="{{ App\Models\Gap::STATUS_RESUELTO }}">Resuelto</option>
                                    <option value="{{ App\Models\Gap::STATUS_VERIFICADO }}">Verificado</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div class="form-text text-secondary">
                                    <i class="bi bi-info-circle me-1"></i>No se permite saltar pasos del ciclo de vida (ej. de Abierto directo a Verificado).
                                </div>
                            </div>

                            {{-- Descripción --}}
                            <div class="mb-3">
                                <label class="form-label text-secondary small">Descripción detallada</label>
                                <textarea class="form-control bg-dark text-white border-secondary @error('description') is-invalid @enderror"
                                          rows="3"
                                          wire:model="description"></textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeEditModal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btn-save-gap">
                                <i class="bi bi-check2 me-1"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         Modal de Detalle y Bitácora (RNF-06)
         ========================================================================= --}}
    @if ($showDetailModal && $viewingGap)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title fw-bold">
                            <i class="bi bi-clock-history text-info me-2"></i>Historial y Bitácora del Gap: {{ $viewingGap->code }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDetailModal"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Resumen del Gap --}}
                        <div class="card bg-secondary bg-opacity-10 border-secondary mb-4 p-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <span class="text-secondary small d-block">Título</span>
                                    <strong class="text-white">{{ $viewingGap->title }}</strong>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-secondary small d-block">Severidad</span>
                                    <span class="badge {{ $viewingGap->severityBadgeColor() }}">{{ $viewingGap->severity }}</span>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-secondary small d-block">Estado</span>
                                    <span class="badge {{ $viewingGap->statusBadgeColor() }}">{{ $viewingGap->status }}</span>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-secondary small d-block">Responsable</span>
                                    <span class="text-light">{{ $viewingGap->assignedTo->name ?? 'Sin asignar' }}</span>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-secondary small d-block">Fecha Límite</span>
                                    <span class="{{ $viewingGap->isOverdue() ? 'text-danger fw-bold' : 'text-light' }}">
                                        {{ $viewingGap->due_date ? $viewingGap->due_date->format('d/m/Y') : 'Sin fecha asignada' }}
                                    </span>
                                </div>
                                @if ($viewingGap->description)
                                    <div class="col-12 mt-2 pt-2 border-top border-secondary">
                                        <span class="text-secondary small d-block">Descripción</span>
                                        <p class="mb-0 small text-light">{{ $viewingGap->description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Bitácora de Cambios (RNF-06) --}}
                        <h6 class="fw-bold mb-3 text-info">
                            <i class="bi bi-journal-text me-2"></i>Registros en Bitácora (RNF-06)
                        </h6>

                        @if ($viewingGap->logs->isEmpty())
                            <div class="text-center py-4 text-secondary">
                                <i class="bi bi-journal-x fs-2 d-block mb-1"></i>
                                <p class="small mb-0">No se han registrado modificaciones en la bitácora todavía.</p>
                            </div>
                        @else
                            <div class="timeline">
                                <div class="list-group list-group-flush">
                                    @foreach ($viewingGap->logs as $log)
                                        <div class="list-group-item bg-transparent text-light border-secondary py-3 px-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div>
                                                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary me-2">
                                                        {{ ucfirst($log->field) }}
                                                    </span>
                                                    <span class="fw-semibold text-white">{{ $log->description }}</span>
                                                </div>
                                                <small class="text-secondary">
                                                    {{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '' }}
                                                </small>
                                            </div>

                                            <div class="small text-secondary d-flex gap-3 mt-1">
                                                <span>
                                                    <i class="bi bi-person me-1"></i>{{ $log->user->name ?? 'Sistema' }}
                                                </span>
                                                @if ($log->old_value !== null || $log->new_value !== null)
                                                    <span>
                                                        Valor anterior: <code class="text-danger">{{ $log->old_value ?? 'vacío' }}</code>
                                                        → Nuevo: <code class="text-success">{{ $log->new_value ?? 'vacío' }}</code>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
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
</div>
