<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 font-weight-bold text-white mb-1">Appraisals CMMI</h2>
            <p class="text-secondary mb-0">Preparación y seguimiento de appraisals bajo el modelo CMMI V3.0.</p>
        </div>
        @can('create', App\Models\Appraisal::class)
            <div>
                <button wire:click="openCreateModal" class="btn btn-primary d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-clipboard-plus"></i> Nuevo Appraisal
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

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-25 text-primary rounded-3 p-3 d-inline-flex">
                        <i class="bi bi-clipboard-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small">Total Appraisals</div>
                        <div class="fs-4 fw-bold">{{ $statusCounts['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 text-warning rounded-3 p-3 d-inline-flex">
                        <i class="bi bi-pencil-square fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small">Borrador</div>
                        <div class="fs-4 fw-bold text-warning">{{ $statusCounts['borrador'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-25 text-success rounded-3 p-3 d-inline-flex">
                        <i class="bi bi-play-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small">Activos</div>
                        <div class="fs-4 fw-bold text-success">{{ $statusCounts['activo'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-secondary bg-opacity-25 text-light rounded-3 p-3 d-inline-flex">
                        <i class="bi bi-lock fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small">Cerrados</div>
                        <div class="fs-4 fw-bold text-secondary">{{ $statusCounts['cerrado'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table & Filters --}}
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
                               placeholder="Buscar appraisal o proyecto...">
                    </div>
                </div>
                <div class="col-md-6 col-lg-auto">
                    <div class="btn-group" role="group" aria-label="Filtro por estado">
                        <button type="button"
                                wire:click="setStatusFilter('all')"
                                class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Todos ({{ $statusCounts['total'] }})
                        </button>
                        <button type="button"
                                wire:click="setStatusFilter('borrador')"
                                class="btn btn-sm {{ $statusFilter === 'borrador' ? 'btn-warning text-dark' : 'btn-outline-secondary' }}">
                            Borrador ({{ $statusCounts['borrador'] }})
                        </button>
                        <button type="button"
                                wire:click="setStatusFilter('activo')"
                                class="btn btn-sm {{ $statusFilter === 'activo' ? 'btn-success' : 'btn-outline-secondary' }}">
                            Activo ({{ $statusCounts['activo'] }})
                        </button>
                        <button type="button"
                                wire:click="setStatusFilter('cerrado')"
                                class="btn btn-sm {{ $statusFilter === 'cerrado' ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">
                            Cerrado ({{ $statusCounts['cerrado'] }})
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 border-secondary">
                    <thead>
                        <tr class="table-secondary text-white">
                            <th scope="col">Appraisal</th>
                            <th scope="col">Proyecto</th>
                            <th scope="col">Dominio</th>
                            <th scope="col">Nivel Objetivo</th>
                            <th scope="col">Fecha Meta</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($appraisals as $appraisal)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white">{{ $appraisal->name }}</div>
                                    <small class="text-secondary">{{ App\Models\Appraisal::MODELO_REFERENCIA }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-light">
                                        <i class="bi bi-folder2 me-1"></i>{{ $appraisal->project->name }}
                                    </span>
                                    <div class="small text-secondary font-monospace">{{ $appraisal->project->code }}</div>
                                </td>
                                <td>
                                    <span class="text-light">{{ $appraisal->domain }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-dark fw-bold">
                                        Nivel {{ $appraisal->target_level }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-light">{{ $appraisal->target_date->format('d/m/Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $appraisal->statusBadgeColor() }} fs-6 fw-normal px-2 py-1">
                                        {{ ucfirst($appraisal->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    {{-- Scope / Alcance CMMI button --}}
                                    <a href="{{ route('appraisals.scope', $appraisal->id) }}"
                                       class="btn btn-sm btn-outline-info me-1"
                                       title="Alcance CMMI">
                                        <i class="bi bi-diagram-3 me-1"></i>Alcance
                                    </a>

                                    {{-- Consultar Prácticas del Alcance (HU-08) --}}
                                    <a href="{{ route('appraisals.practices', $appraisal->id) }}"
                                       class="btn btn-sm btn-outline-light me-1"
                                       title="Consultar prácticas del alcance">
                                        <i class="bi bi-list-check me-1"></i>Prácticas
                                    </a>

                                    {{-- Edit (Admin only, in borrador) --}}
                                    @can('update', $appraisal)
                                        <button wire:click="openEditModal({{ $appraisal->id }})"
                                                class="btn btn-sm btn-outline-warning me-1"
                                                title="Editar datos">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endcan

                                    {{-- State Transitions (Admin and Gestor de Procesos) --}}
                                    @can('updateStatus', $appraisal)
                                        @if ($appraisal->isBorrador())
                                            <button wire:click="activateAppraisal({{ $appraisal->id }})"
                                                    wire:confirm="¿Activar el appraisal '{{ $appraisal->name }}'? El proyecto quedará bloqueado."
                                                    class="btn btn-sm btn-success me-1"
                                                    title="Activar appraisal">
                                                <i class="bi bi-play-fill me-1"></i>Activar
                                            </button>
                                        @elseif ($appraisal->isActivo())
                                            <button wire:click="closeAppraisal({{ $appraisal->id }})"
                                                    wire:confirm="¿Cerrar el appraisal '{{ $appraisal->name }}'? Pasará a modo solo lectura definitiva."
                                                    class="btn btn-sm btn-outline-secondary me-1"
                                                    title="Cerrar appraisal">
                                                <i class="bi bi-lock-fill me-1"></i>Cerrar
                                            </button>
                                        @endif
                                    @endcan

                                    @if ($appraisal->isCerrado())
                                        <span class="badge bg-dark border border-secondary text-secondary">
                                            <i class="bi bi-lock me-1"></i>Solo lectura
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">
                                    <i class="bi bi-clipboard-x fs-3 d-block mb-2"></i>
                                    No se encontraron appraisals registrados o visibles.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($appraisals->hasPages())
                <div class="mt-3">
                    {{ $appraisals->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Form Modal (Create / Edit) --}}
    @if ($showFormModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">
                            <i class="bi {{ $isEditing ? 'bi-pencil-square' : 'bi-clipboard-plus' }} me-2 text-primary"></i>
                            {{ $isEditing ? 'Editar Appraisal' : 'Nuevo Appraisal CMMI' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="closeFormModal" aria-label="Cerrar"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            {{-- Modelo de Referencia (Constante fija) --}}
                            <div class="mb-3">
                                <label class="form-label text-secondary small mb-1">Modelo de Referencia</label>
                                <input type="text" class="form-control bg-secondary bg-opacity-25 text-white border-secondary" value="{{ App\Models\Appraisal::MODELO_REFERENCIA }}" readonly>
                                <div class="form-text text-secondary">Estándar institucional constante para todos los appraisals.</div>
                            </div>

                            {{-- Proyecto selector --}}
                            <div class="mb-3">
                                <label for="appraisal-project" class="form-label">
                                    Proyecto <span class="text-danger">*</span>
                                </label>
                                @if ($isEditing && $currentStatus !== App\Models\Appraisal::STATUS_BORRADOR)
                                    <select id="appraisal-project" class="form-select bg-secondary bg-opacity-25 text-white border-secondary" disabled>
                                        @foreach ($availableProjects as $p)
                                            <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>
                                                {{ $p->name }} ({{ $p->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text text-warning">
                                        <i class="bi bi-lock-fill me-1"></i>El proyecto no se puede modificar una vez que el appraisal está activo.
                                    </div>
                                @else
                                    <select id="appraisal-project"
                                            wire:model="projectId"
                                            class="form-select bg-dark text-white border-secondary @error('projectId') is-invalid @enderror">
                                        <option value="">— Seleccionar proyecto activo —</option>
                                        @foreach ($availableProjects as $p)
                                            <option value="{{ $p->id }}">
                                                {{ $p->name }} ({{ $p->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('projectId')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>

                            {{-- Nombre del Appraisal --}}
                            <div class="mb-3">
                                <label for="appraisal-name" class="form-label">
                                    Nombre del Appraisal <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       id="appraisal-name"
                                       wire:model="name"
                                       class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror"
                                       placeholder="Ej. Appraisal CMMI DEV Nivel 3 - H1 2026">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Dominio CMMI --}}
                            <div class="mb-3">
                                <label for="appraisal-domain" class="form-label">
                                    Dominio CMMI <span class="text-danger">*</span>
                                </label>
                                <select id="appraisal-domain"
                                        wire:model="domain"
                                        class="form-select bg-dark text-white border-secondary @error('domain') is-invalid @enderror">
                                    <option value="Development">Development (DEV)</option>
                                    <option value="Services">Services (SVC)</option>
                                    <option value="Security">Security (SEC)</option>
                                    <option value="Safety">Safety (SAF)</option>
                                    <option value="Supplier Management">Supplier Management (SPM)</option>
                                    <option value="Virtual">Virtual</option>
                                </select>
                                @error('domain')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row g-3 mb-3">
                                {{-- Nivel Objetivo --}}
                                <div class="col-md-6">
                                    <label for="appraisal-level" class="form-label">
                                        Nivel Objetivo <span class="text-danger">*</span>
                                    </label>
                                    <select id="appraisal-level"
                                            wire:model="targetLevel"
                                            class="form-select bg-dark text-white border-secondary @error('targetLevel') is-invalid @enderror">
                                        <option value="1">Nivel 1 — Inicial</option>
                                        <option value="2">Nivel 2 — Gestionado</option>
                                        <option value="3">Nivel 3 — Definido</option>
                                        <option value="4">Nivel 4 — Gestionado Cuantitativamente</option>
                                        <option value="5">Nivel 5 — Optimizado</option>
                                    </select>
                                    @error('targetLevel')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Fecha Meta --}}
                                <div class="col-md-6">
                                    <label for="appraisal-date" class="form-label">
                                        Fecha Meta <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                           id="appraisal-date"
                                           wire:model="targetDate"
                                           class="form-control bg-dark text-white border-secondary @error('targetDate') is-invalid @enderror">
                                    @error('targetDate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-outline-secondary" wire:click="closeFormModal">Cancelar</button>
                            <button type="submit" class="btn btn-primary fw-semibold">
                                <i class="bi bi-check-lg me-1"></i>
                                {{ $isEditing ? 'Guardar Cambios' : 'Crear Appraisal' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
