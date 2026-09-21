<div class="container py-4">
    {{-- ===================================================================
         Header
    ==================================================================== --}}
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('proyectos.index') }}" wire:navigate class="text-decoration-none text-secondary">
                            <i class="bi bi-folder2-open me-1"></i>Cartera de Proyectos
                        </a>
                    </li>
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $project->name }}</li>
                </ol>
            </nav>
            <h2 class="h3 text-white fw-bold mb-1">{{ $project->name }}</h2>
            <p class="text-secondary mb-0">
                <span class="font-monospace me-3">
                    <i class="bi bi-hash me-1"></i>{{ $project->code }}
                </span>
                <span class="me-3">
                    <i class="bi bi-calendar-event me-1"></i>Inicio: {{ $project->start_date->format('d/m/Y') }}
                </span>
                @if ($project->isActive())
                    <span class="badge bg-success fs-6 fw-normal px-2 py-1">
                        <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i>Activo
                    </span>
                @else
                    <span class="badge bg-secondary fs-6 fw-normal px-2 py-1">
                        <i class="bi bi-lock me-1"></i>Cerrado
                    </span>
                @endif
            </p>
        </div>
        <a href="{{ route('proyectos.index') }}"
           wire:navigate
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    @if (! $project->isActive())
        <div class="alert alert-secondary border-secondary mb-4" role="alert">
            <i class="bi bi-lock-fill me-2"></i>
            <strong>Proyecto cerrado.</strong> No se pueden agregar ni modificar evidencias.
        </div>
    @endif

    <div class="row g-4">

        {{-- ================================================================
             Integrantes
        ================================================================= --}}
        <div class="col-12">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people-fill me-2 text-warning"></i>Integrantes del Proyecto
                    </h5>
                </div>
                <div class="card-body">
                    @if ($project->users->isEmpty())
                        <p class="text-secondary fst-italic mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Este proyecto no tiene integrantes asignados aún.
                        </p>
                    @else
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($project->users as $member)
                                <div class="d-flex align-items-center gap-2 bg-secondary bg-opacity-25 border border-secondary rounded px-3 py-2">
                                    <i class="bi bi-person-circle text-secondary fs-5"></i>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold text-white small">{{ $member->name }}</span>
                                            <span class="badge {{ $member->colorDeRol() }}" style="font-size:.65rem;">
                                                {{ $member->etiquetaDeRol() }}
                                            </span>
                                        </div>
                                        <div class="text-secondary" style="font-size:.75rem;">{{ $member->email }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================================================================
             Appraisals asociados (RF-06)
        ================================================================= --}}
        <div class="col-12">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-check me-2 text-info"></i>Appraisals
                        @if ($project->appraisals->isNotEmpty())
                            <span class="badge bg-info bg-opacity-25 text-info ms-2">{{ $project->appraisals->count() }}</span>
                        @endif
                    </h5>
                    @can('ver-appraisals')
                        <a href="{{ route('appraisals.index') }}" wire:navigate class="btn btn-outline-info btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Ver todos
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    @if ($project->appraisals->isEmpty())
                        <p class="text-secondary fst-italic mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Este proyecto no tiene appraisals asociados aún.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle mb-0">
                                <thead class="border-secondary">
                                    <tr class="text-secondary small text-uppercase">
                                        <th>Nombre</th>
                                        <th>Dominio</th>
                                        <th class="text-center">Nivel</th>
                                        <th>Fecha Meta</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($project->appraisals->sortByDesc('id') as $appraisal)
                                        <tr>
                                            <td class="fw-semibold">{{ $appraisal->name }}</td>
                                            <td>{{ $appraisal->domain }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-primary bg-opacity-25 text-primary">
                                                    Nivel {{ $appraisal->target_level }}
                                                </span>
                                            </td>
                                            <td>{{ $appraisal->target_date->format('d/m/Y') }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $appraisal->statusBadgeColor() }}">
                                                    {{ ucfirst($appraisal->status) }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('appraisals.scope', $appraisal->id) }}" class="btn btn-outline-info btn-sm">
                                                    <i class="bi bi-diagram-3 me-1"></i>Alcance
                                                </a>
                                                <a href="{{ route('appraisals.practices', $appraisal->id) }}" class="btn btn-outline-light btn-sm ms-1" title="Consultar prácticas">
                                                    <i class="bi bi-list-check me-1"></i>Prácticas
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ================================================================
             Evidencias — placeholder (HU-12)
        ================================================================= --}}
        <div class="col-12">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-text me-2 text-success"></i>Evidencias
                    </h5>
                </div>
                <div class="card-body text-center py-5">
                    <i class="bi bi-hourglass-split fs-1 text-secondary mb-3 d-block"></i>
                    <p class="text-secondary mb-1">Módulo de Evidencias aún no implementado.</p>
                    <small class="text-secondary fst-italic">Pendiente: HU-12</small>
                </div>
            </div>
        </div>

    </div>
</div>
