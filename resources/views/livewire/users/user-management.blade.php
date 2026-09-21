<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-secondary mb-0">Administración de acceso y asignación de roles para la plataforma CMMI de DIMA LTDA.</p>
        </div>
        @can('create', App\Models\User::class)
            <div>
                <button wire:click="openCreateModal" class="btn btn-primary d-flex align-items-center gap-2 fw-semibold">
                    <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
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
            <div class="row g-3 align-items-center mb-3">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-secondary border-secondary border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control bg-dark text-white border-secondary border-start-0" placeholder="Buscar por nombre o correo...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0 border-secondary">
                    <thead>
                        <tr class="table-secondary text-white">
                            <th scope="col">ID</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Correo Electrónico</th>
                            <th scope="col">Rol Asignado</th>
                            <th scope="col">Estado</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="fw-bold">{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge {{ $user->colorDeRol() }} fs-6 fw-normal px-2 py-1">{{ $user->etiquetaDeRol() }}</span>
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge bg-success fs-6 fw-normal px-2 py-1"><i class="bi bi-check-circle me-1"></i>Activo</span>
                                    @else
                                        <span class="badge bg-danger fs-6 fw-normal px-2 py-1"><i class="bi bi-x-circle me-1"></i>Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('update', $user)
                                        <button wire:click="openEditModal({{ $user->id }})" class="btn btn-sm btn-outline-primary me-1" title="Editar usuario">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    @endcan

                                    @can('delete', $user)
                                        <button wire:click="toggleStatus({{ $user->id }})" 
                                                class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" 
                                                title="{{ $user->is_active ? 'Dar de baja usuario' : 'Reactivar usuario' }}">
                                            @if ($user->is_active)
                                                <i class="bi bi-person-x"></i> Dar de baja
                                            @else
                                                <i class="bi bi-person-check"></i> Reactivar
                                            @endif
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">
                                    No se encontraron usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    <!-- Modal Formulario Usuario -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.7);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header bg-primary text-white border-secondary">
                        <h5 class="modal-title">
                            @if($isEditing)
                                <i class="bi bi-pencil-square me-2"></i>Editar Usuario
                            @else
                                <i class="bi bi-person-plus me-2"></i>Registrar Nuevo Usuario
                            @endif
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeModal" aria-label="Cerrar"></button>
                    </div>
                    <form wire:submit.prevent="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold text-white">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" id="name" wire:model="name" class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror" placeholder="Ej: Juan Pérez">
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold text-white">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" id="email" wire:model="email" class="form-control bg-dark text-white border-secondary @error('email') is-invalid @enderror" placeholder="usuario@dima.cl">
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold text-white">
                                    Contraseña 
                                    @if($isEditing)
                                        <small class="text-secondary">(Dejar en blanco para mantener la actual)</small>
                                    @else
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input type="password" id="password" wire:model="password" class="form-control bg-dark text-white border-secondary @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label fw-semibold text-white">Rol de Usuario <span class="text-danger">*</span></label>
                                <select id="role" wire:model="role" class="form-select bg-dark text-white border-secondary @error('role') is-invalid @enderror">
                                    @foreach($roles as $r)
                                        <option value="{{ $r->name }}">{{ $r->etiqueta() }}</option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" id="is_active" wire:model="is_active" class="form-check-input">
                                <label for="is_active" class="form-check-label text-white">Usuario Activo</label>
                            </div>
                        </div>

                        <div class="modal-footer bg-dark border-secondary">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                            <button type="submit" class="btn btn-primary fw-semibold">
                                @if($isEditing)
                                    Guardar Cambios
                                @else
                                    Crear Usuario
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
