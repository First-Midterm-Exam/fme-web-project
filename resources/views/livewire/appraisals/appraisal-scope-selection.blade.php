<div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('proyectos.index') }}" class="text-decoration-none">Proyectos</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('proyectos.show', $appraisal->project_id) }}" class="text-decoration-none">{{ $appraisal->project->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('appraisals.index') }}" class="text-decoration-none">Appraisals</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Alcance CMMI</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 fw-bold">
                <i class="bi bi-diagram-3 text-primary me-2"></i>Alcance CMMI V3.0
                <span class="fs-6 fw-normal text-muted ms-2">— {{ $appraisal->name }}</span>
            </h1>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('appraisals.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver a Appraisals
            </a>
            @if ($canEdit)
                <button type="button" wire:click="save" class="btn btn-primary shadow-sm" id="btn-save-scope-header">
                    <i class="bi bi-check2-circle me-1"></i> Guardar Alcance
                </button>
            @endif
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (! $appraisal->isBorrador())
        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-lock-fill fs-4 me-3 text-warning"></i>
            <div>
                <strong>Alcance Congelado (Estado: {{ ucfirst($appraisal->status) }})</strong>
                <p class="mb-0 small text-muted">
                    El appraisal se encuentra en estado <strong>{{ $appraisal->status }}</strong>. La definición de alcance es de <strong>solo lectura</strong> para todos los roles. Para realizar modificaciones se debe regresar el appraisal al estado borrador.
                </p>
            </div>
        </div>
    @elseif (! $canEdit)
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-eye-fill fs-4 me-3 text-info"></i>
            <div>
                <strong>Modo Lectura</strong>
                <p class="mb-0 small text-muted">
                    Usted tiene permisos de visualización del alcance del appraisal para el proyecto <strong>{{ $appraisal->project->name }}</strong>. Los cambios solo pueden ser realizados por Gestor de Procesos o Administrador.
                </p>
            </div>
        </div>
    @else
        <div class="alert alert-primary border-0 shadow-sm d-flex align-items-center mb-4">
            <i class="bi bi-pencil-square fs-4 me-3 text-primary"></i>
            <div>
                <strong>Modo Edición Habilitado (Estado: Borrador)</strong>
                <p class="mb-0 small text-muted">
                    Seleccione las Practice Areas completas o prácticas individuales para conformar el alcance de evaluación CMMI V3.0. Al activar el appraisal, el alcance quedará congelado automáticamente.
                </p>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary me-3">
                        <i class="bi bi-list-check fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Prácticas en Alcance</div>
                        <div class="h4 mb-0 fw-bold" id="scope-practices-count">{{ $selectedCount }} <span class="fs-6 fw-normal text-muted">/ {{ $totalPractices }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-info-subtle text-info me-3">
                        <i class="bi bi-grid-3x3-gap fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Áreas Seleccionadas</div>
                        <div class="h4 mb-0 fw-bold" id="scope-areas-count">{{ $selectedAreasCount }} <span class="fs-6 fw-normal text-muted">/ {{ $totalAreasCount }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 p-3 bg-success-subtle text-success me-3">
                        <i class="bi bi-bullseye fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Nivel Objetivo</div>
                        <div class="h4 mb-0 fw-bold">Nivel {{ $appraisal->target_level }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold mb-2">Desglose por Nivel</div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-secondary-subtle text-secondary border">N1: {{ $byLevel[1] ?? 0 }}</span>
                        <span class="badge bg-primary-subtle text-primary border">N2: {{ $byLevel[2] ?? 0 }}</span>
                        <span class="badge bg-success-subtle text-success border">N3: {{ $byLevel[3] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" placeholder="Buscar práctica o código CMMI..." wire:model.live.debounce.300ms="search">
                    </div>
                </div>

                <div class="col-md-7 d-flex flex-wrap justify-content-md-end gap-2">
                    @if ($canEdit)
                        <button type="button" wire:click="selectTargetLevel({{ $appraisal->target_level }})" class="btn btn-outline-primary btn-sm" id="btn-select-target-level">
                            <i class="bi bi-magic me-1"></i> Seleccionar hasta Nivel {{ $appraisal->target_level }}
                        </button>
                        <button type="button" wire:click="selectAll" class="btn btn-outline-secondary btn-sm" id="btn-select-all">
                            <i class="bi bi-check-all me-1"></i> Seleccionar Todo
                        </button>
                        <button type="button" wire:click="clearAll" class="btn btn-outline-danger btn-sm" id="btn-clear-all">
                            <i class="bi bi-x-lg me-1"></i> Limpiar Selección
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @forelse ($areas as $area)
            @php
                $areaPracticeIds = $area->practices->pluck('id')->toArray();
                $areaSelectedCount = count(array_intersect($areaPracticeIds, $selectedPracticeIds));
                $areaTotalCount = count($areaPracticeIds);
                $isAllAreaSelected = $areaTotalCount > 0 && $areaSelectedCount === $areaTotalCount;
                $isPartiallySelected = $areaSelectedCount > 0 && $areaSelectedCount < $areaTotalCount;
            @endphp

            <div class="col-12" wire:key="area-{{ $area->id }}">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check m-0">
                                <input class="form-check-input area-checkbox"
                                    type="checkbox"
                                    id="area-check-{{ $area->id }}"
                                    wire:click="toggleArea({{ $area->id }})"
                                    @checked($isAllAreaSelected)
                                    @disabled(! $canEdit)>
                                <label class="form-check-label fw-bold text-dark fs-6" for="area-check-{{ $area->id }}">
                                    <span class="badge bg-dark-subtle text-dark border me-2">{{ $area->code }}</span>
                                    {{ $area->name }}
                                </label>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if ($isAllAreaSelected)
                                <span class="badge bg-success-subtle text-success border">
                                    <i class="bi bi-check-circle me-1"></i> Completa ({{ $areaSelectedCount }}/{{ $areaTotalCount }})
                                </span>
                            @elseif ($isPartiallySelected)
                                <span class="badge bg-warning-subtle text-warning-emphasis border">
                                    <i class="bi bi-dash-circle me-1"></i> Parcial ({{ $areaSelectedCount }}/{{ $areaTotalCount }})
                                </span>
                            @else
                                <span class="badge bg-light text-muted border">
                                    0 / {{ $areaTotalCount }} seleccionadas
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse ($area->practices as $practice)
                                @php
                                    $isPracticeSelected = in_array($practice->id, $selectedPracticeIds, true);
                                @endphp
                                <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 px-4 {{ $isPracticeSelected ? 'bg-primary-subtle bg-opacity-10' : '' }}"
                                     wire:key="practice-{{ $practice->id }}">
                                    <div class="form-check d-flex align-items-center m-0 flex-grow-1">
                                        <input class="form-check-input practice-checkbox me-3"
                                            type="checkbox"
                                            id="practice-check-{{ $practice->id }}"
                                            wire:click="togglePractice({{ $practice->id }})"
                                            @checked($isPracticeSelected)
                                            @disabled(! $canEdit)>
                                        <label class="form-check-label w-100 cursor-pointer" for="practice-check-{{ $practice->id }}">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary text-white fw-bold">{{ $practice->code }}</span>
                                                <span class="fw-medium text-dark">{{ $practice->name }}</span>
                                            </div>
                                        </label>
                                    </div>

                                    <div>
                                        @if ($practice->level === 1)
                                            <span class="badge bg-secondary-subtle text-secondary border">Nivel 1</span>
                                        @elseif ($practice->level === 2)
                                            <span class="badge bg-primary-subtle text-primary border">Nivel 2</span>
                                        @elseif ($practice->level === 3)
                                            <span class="badge bg-success-subtle text-success border">Nivel 3</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info border">Nivel {{ $practice->level }}</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-muted text-center py-3">
                                    No se encontraron prácticas con los filtros actuales.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm text-center py-5">
                    <i class="bi bi-folder-x fs-1 text-muted mb-2"></i>
                    <h5 class="text-muted">No hay datos en el catálogo CMMI</h5>
                    <p class="text-muted small">Ejecute el seeder de catálogo CMMI para cargar las Practice Areas y Prácticas.</p>
                </div>
            </div>
        @endforelse
    </div>

    @if ($canEdit)
        <div class="card border-0 shadow-lg mt-4 bg-white sticky-bottom py-2">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary fs-6">{{ $selectedCount }}</span>
                    <span class="fw-bold">prácticas seleccionadas para este appraisal</span>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" wire:click="save" class="btn btn-primary px-4 shadow-sm" id="btn-save-scope-footer">
                        <i class="bi bi-check2-circle me-1"></i> Guardar Alcance
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
