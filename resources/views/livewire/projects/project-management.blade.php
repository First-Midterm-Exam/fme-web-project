<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 font-weight-bold text-white mb-1">Cartera de Proyectos</h2>
            <p class="text-secondary mb-0">Gestión de proyectos de la empresa para la plataforma CMMI de DIMA LTDA.</p>
        </div>
        @can('create', App\Models\Project::class)
            <div>
                <button wire:click="openCreateModal" class="btn btn-primary d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-folder-plus"></i> Nuevo Proyecto
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

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-25 text-primary rounded-3 p-3 d-inline-flex">
                        <i class="bi bi-folder2-open fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small">Total Proyectos</div>
                        <div class="fs-4 fw-bold">{{ $statusCounts['total'] }}</div>
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
                        <div class="fs-4 fw-bold text-success">{{ $statusCounts['activos'] }}</div>
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
                        <div class="fs-4 fw-bold text-secondary">{{ $statusCounts['cerrados'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-25 text-info rounded-3 p-3 d-inline-flex">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <div>
                        <div class="text-secondary small">Integrantes</div>
                        <div class="fs-4 fw-bold text-info">{{ $statusCounts['miembros'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                               placeholder="Buscar por nombre o código...">
                    </div>
                </div>
                <div class="col-md-6 col-lg-auto">
                    <div class="btn-group" role="group" aria-label="Filtro de estado">
                        <button type="button"
                                wire:click="setStatusFilter('all')"
                                class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Todos ({{ $statusCounts['total'] }})
                        </button>
                        <button type="button"
                                wire:click="setStatusFilter('activo')"
                                class="btn btn-sm {{ $statusFilter === 'activo' ? 'btn-success' : 'btn-outline-secondary' }}">
                            Activos ({{ $statusCounts['activos'] }})
                        </button>
                        <button type="button"
                                wire:click="setStatusFilter('cerrado')"
                                class="btn btn-sm {{ $statusFilter === 'cerrado' ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">
                            Cerrados ({{ $statusCounts['cerrados'] }})
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 border-secondary">
                    <thead>
                        <tr class="table-secondary text-white">
                            <th scope="col">Código</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Fecha Inicio</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Integrantes</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $project->code }}</td>
                                <td>{{ $project->name }}</td>
                                <td>{{ $project->start_date->format('d/m/Y') }}</td>
                                <td>
                                    @if ($project->isActive())
                                        <span class="badge bg-success fs-6 fw-normal px-2 py-1">
                                            <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Activo
                                        </span>
                                    @else
                                        <span class="badge bg-secondary fs-6 fw-normal px-2 py-1">
                                            <i class="bi bi-lock me-1"></i>Cerrado
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-dark border border-secondary text-white">
                                        <i class="bi bi-people me-1"></i>{{ $project->users->count() }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('proyectos.show', $project) }}"
                                       wire:navigate
                                       class="btn btn-sm btn-outline-info me-1"
                                       title="Ver detalle">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>

                                    @can('update', $project)
                                        <button wire:click="openEditModal({{ $project->id }})"
                                                class="btn btn-sm btn-outline-primary me-1"
                                                title="Editar proyecto">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    @endcan

                                    @can('manageMembers', $project)
                                        <button wire:click="openMembersModal({{ $project->id }})"
                                                class="btn btn-sm btn-outline-warning me-1"
                                                title="Gestionar integrantes">
                                            <i class="bi bi-people-fill"></i>
                                        </button>
                                    @endcan

                                    @can('close', $project)
                                        <button wire:click="closeProject({{ $project->id }})"
                                                wire:confirm="¿Está seguro de que desea cerrar el proyecto «{{ $project->name }}»? Esta acción no se puede deshacer."
                                                class="btn btn-sm btn-outline-danger"
                                                title="Cerrar proyecto">
                                            <i class="bi bi-lock-fill"></i> Cerrar
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">
                                    <i class="bi bi-folder-x fs-3 d-block mb-2"></i>
                                    No se encontraron proyectos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $projects->links() }}
            </div>
        </div>
    </div>

    {{-- ====================================================================
         Modal: Crear / Editar Proyecto
    ===================================================================== --}}
    @if ($showFormModal)
        <div class="modal fade show d-block"
             tabindex="-1"
             style="background-color: rgba(0,0,0,0.7);"
             aria-modal="true"
             role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header bg-primary text-white border-secondary">
                        <h5 class="modal-title">
                            @if ($isEditing)
                                <i class="bi bi-pencil-square me-2"></i>Editar Proyecto
                            @else
                                <i class="bi bi-folder-plus me-2"></i>Nuevo Proyecto
                            @endif
                        </h5>
                        <button type="button"
                                class="btn-close btn-close-white"
                                wire:click="closeFormModal"
                                aria-label="Cerrar"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="proj-name" class="form-label fw-semibold text-white">
                                    Nombre del Proyecto <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       id="proj-name"
                                       wire:model="name"
                                       class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror"
                                       placeholder="Ej: Sistema de Gestión CMMI">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="proj-code" class="form-label fw-semibold text-white">
                                    Código <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       id="proj-code"
                                       wire:model="code"
                                       class="form-control bg-dark text-white border-secondary font-monospace @error('code') is-invalid @enderror"
                                       placeholder="Ej: PROJ-001">
                                @error('code')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="proj-start-date" class="form-label fw-semibold text-white">
                                    Fecha de Inicio <span class="text-danger">*</span>
                                </label>
                                <input type="date"
                                       id="proj-start-date"
                                       wire:model="startDate"
                                       class="form-control bg-dark text-white border-secondary @error('startDate') is-invalid @enderror">
                                @error('startDate')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="modal-footer bg-dark border-secondary">
                            <button type="button" class="btn btn-secondary" wire:click="closeFormModal">
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary fw-semibold">
                                @if ($isEditing)
                                    Guardar Cambios
                                @else
                                    Crear Proyecto
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ====================================================================
         Modal: Gestionar Integrantes
    ===================================================================== --}}
    @if ($showMembersModal && $membersProject)
        <div class="modal fade show d-block"
             tabindex="-1"
             style="background-color: rgba(0,0,0,0.7);"
             aria-modal="true"
             role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header bg-warning text-dark border-secondary">
                        <h5 class="modal-title">
                            <i class="bi bi-people-fill me-2"></i>
                            Integrantes — {{ $membersProject->name }}
                        </h5>
                        <button type="button"
                                class="btn-close"
                                wire:click="closeMembersModal"
                                aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @if (session()->has('membersMessage'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i> {{ session('membersMessage') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        @endif

                        {{-- Add member --}}
                        <div class="row g-2 align-items-end mb-4">
                            <div class="col">
                                <label for="members-user-select" class="form-label fw-semibold text-white">
                                    Agregar integrante
                                </label>
                                <select id="members-user-select"
                                        wire:model="selectedUserId"
                                        class="form-select bg-dark text-white border-secondary @error('selectedUserId') is-invalid @enderror">
                                    <option value="">— Seleccionar usuario —</option>
                                    @foreach ($availableUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} [{{ $u->etiquetaDeRol() }}] ({{ $u->email }})</option>
                                    @endforeach
                                </select>
                                @error('selectedUserId')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-auto">
                                <button wire:click="addMember"
                                        class="btn btn-warning text-dark fw-semibold">
                                    <i class="bi bi-person-plus-fill me-1"></i> Agregar
                                </button>
                            </div>
                        </div>

                        {{-- Current members --}}
                        <h6 class="text-secondary fw-semibold mb-2">
                            <i class="bi bi-people me-1"></i> Integrantes actuales
                        </h6>
                        @if ($membersProject->users->isEmpty())
                            <p class="text-secondary fst-italic">Este proyecto no tiene integrantes asignados.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($membersProject->users as $member)
                                    <li class="list-group-item bg-dark text-white border-secondary d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-person-circle text-secondary fs-5"></i>
                                            <div>
                                                <span class="fw-semibold">{{ $member->name }}</span>
                                                <span class="badge {{ $member->colorDeRol() }} ms-2 fw-normal small">
                                                    {{ $member->etiquetaDeRol() }}
                                                </span>
                                                <div class="small text-secondary">{{ $member->email }}</div>
                                            </div>
                                        </div>
                                        <button wire:click="removeMember({{ $member->id }})"
                                                wire:confirm="¿Quitar a {{ $member->name }} del proyecto?"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Quitar integrante">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="modal-footer bg-dark border-secondary">
                        <button type="button" class="btn btn-secondary" wire:click="closeMembersModal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
