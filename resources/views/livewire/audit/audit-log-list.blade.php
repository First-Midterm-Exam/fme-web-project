<div>
    <div class="mb-4">
        <h1 class="h3 text-white fw-bold mb-1">
            <i class="bi bi-journal-text me-2 text-info"></i>Bitácora de Auditoría
        </h1>
        <p class="text-secondary small mb-0">Registro de quién cambió qué y cuándo en la plataforma. Solo lectura.</p>
    </div>

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-6 col-lg-3">
                    <label for="filtro-usuario" class="form-label text-secondary small mb-1"><i class="bi bi-person me-1"></i>Usuario</label>
                    <select id="filtro-usuario" wire:model.live="userFilter" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="">— Todos —</option>
                        <option value="sistema">Sistema</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label for="filtro-entidad" class="form-label text-secondary small mb-1"><i class="bi bi-diagram-2 me-1"></i>Entidad</label>
                    <select id="filtro-entidad" wire:model.live="entityFilter" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="">— Todas —</option>
                        @foreach ($entities as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="filtro-accion" class="form-label text-secondary small mb-1"><i class="bi bi-lightning me-1"></i>Acción</label>
                    <select id="filtro-accion" wire:model.live="actionFilter" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="">— Todas —</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="filtro-desde" class="form-label text-secondary small mb-1">Desde</label>
                    <input id="filtro-desde" type="date" wire:model.live="dateFrom" class="form-control form-control-sm bg-dark text-white border-secondary">
                </div>
                <div class="col-6 col-lg-2">
                    <label for="filtro-hasta" class="form-label text-secondary small mb-1">Hasta</label>
                    <input id="filtro-hasta" type="date" wire:model.live="dateTo" class="form-control form-control-sm bg-dark text-white border-secondary">
                </div>
                <div class="col-md-8 col-lg-9">
                    <label for="filtro-buscar" class="form-label text-secondary small mb-1"><i class="bi bi-search me-1"></i>Buscar entidad</label>
                    <input id="filtro-buscar" type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Código, correo o nombre (ej. GAP-0003, EV-0001)...">
                </div>
                <div class="col-md-4 col-lg-3 text-md-end">
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-warning w-100">
                        <i class="bi bi-x-circle me-1"></i>Limpiar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small border-secondary">
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th class="text-end">Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-secondary">
                            <td class="small text-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="small">
                                @if ($log->user)
                                    {{ $log->user->name }}
                                    <div class="text-secondary">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-secondary fst-italic">Sistema</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $log->actionBadgeColor() }}">{{ $log->action }}</span></td>
                            <td class="small">
                                <div class="text-secondary">{{ $log->entityType() }}</div>
                                <span class="font-monospace text-info">{{ $log->entity }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" wire:click="toggle({{ $log->id }})" class="btn btn-sm btn-outline-info">
                                    <i class="bi {{ $expandedId === $log->id ? 'bi-chevron-up' : 'bi-chevron-down' }} me-1"></i>{{ count($log->changes()) }}
                                </button>
                            </td>
                        </tr>
                        @if ($expandedId === $log->id)
                            <tr class="border-secondary">
                                <td colspan="5" class="bg-secondary bg-opacity-10">
                                    @if ($log->changes() === [])
                                        <span class="small text-secondary">Sin detalle de valores.</span>
                                    @else
                                        <table class="table table-sm table-dark mb-0 small">
                                            <thead>
                                                <tr class="text-secondary">
                                                    <th style="width: 25%;">Campo</th>
                                                    <th style="width: 37%;">Antes</th>
                                                    <th style="width: 38%;">Después</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($log->changes() as $change)
                                                    <tr>
                                                        <td class="font-monospace text-info">{{ $change['field'] }}</td>
                                                        <td class="text-danger text-break">{{ $change['old'] }}</td>
                                                        <td class="text-success text-break">{{ $change['new'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                    @if ($log->ip_address)
                                        <div class="small text-secondary mt-2"><i class="bi bi-hdd-network me-1"></i>IP: {{ $log->ip_address }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>No hay registros que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer bg-dark border-secondary">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
