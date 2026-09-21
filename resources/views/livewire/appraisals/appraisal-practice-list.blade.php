<div>
    {{-- Header / Breadcrumb --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('appraisals.index') }}" class="text-decoration-none text-info">Appraisals</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $appraisal->name }}</li>
                    <li class="breadcrumb-item active text-secondary" aria-current="page">Prácticas del Alcance</li>
                </ol>
            </nav>
            <h1 class="h3 text-white fw-bold mb-1">
                <i class="bi bi-list-check me-2 text-info"></i>Prácticas del Alcance
            </h1>
            <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                <span><strong class="text-white">Proyecto:</strong> {{ $appraisal->project->name }} ({{ $appraisal->project->code }})</span>
                <span>•</span>
                <span><strong class="text-white">Nivel Objetivo:</strong> Nivel {{ $appraisal->target_level }}</span>
                <span>•</span>
                <span><strong class="text-white">Dominio:</strong> {{ $appraisal->domain }}</span>
                <span>•</span>
                <span class="badge {{ $appraisal->statusBadgeColor() }}">{{ ucfirst($appraisal->status) }}</span>
                <span>•</span>
                <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary">
                    <i class="bi bi-eye me-1"></i>Modo Consulta
                </span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('appraisals.scope', $appraisal->id) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-diagram-3 me-1"></i>Ver Alcance
            </a>
            <a href="{{ route('appraisals.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Appraisals
            </a>
        </div>
    </div>

    {{-- Metrics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Áreas en Alcance</div>
                    <div class="h4 fw-bold mb-0 text-info">{{ $totalScopeAreas }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Prácticas en Alcance</div>
                    <div class="h4 fw-bold mb-0 text-white">{{ $totalScopePractices }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="text-secondary small card-body py-3">
                    <div class="text-secondary small">Prácticas Filtradas</div>
                    <div class="h4 fw-bold mb-0 text-primary">{{ $filteredCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Estado del Appraisal</div>
                    <div class="h5 fw-bold mb-0">
                        <span class="badge {{ $appraisal->statusBadgeColor() }} fs-6">{{ ucfirst($appraisal->status) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="filter-practice-area" class="form-label text-secondary small mb-1">
                        <i class="bi bi-funnel me-1"></i>Filtrar por Practice Area:
                    </label>
                    <select id="filter-practice-area"
                            wire:model.live="areaFilter"
                            class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="">— Todas las Áreas de Práctica —</option>
                        @foreach ($availableAreas as $area)
                            <option value="{{ $area->id }}">
                                {{ $area->code }} — {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filter-assessment-status" class="form-label text-secondary small mb-1">
                        <i class="bi bi-tag me-1"></i>Filtrar por Estado:
                    </label>
                    <select id="filter-assessment-status"
                            wire:model.live="statusFilter"
                            class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="all">— Todos los Estados —</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st }}">{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 text-md-end">
                    @if ($areaFilter !== null || $statusFilter !== 'all')
                        <button type="button"
                                wire:click="resetFilters"
                                class="btn btn-sm btn-outline-warning w-100 w-md-auto">
                            <i class="bi bi-x-circle me-1"></i>Limpiar Filtros
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Practices List Grouped by Practice Area --}}
    @if ($totalScopePractices === 0)
        <div class="card bg-dark text-white border-secondary shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-inbox fs-1 text-secondary mb-3 d-block"></i>
                <h5 class="fw-bold">Sin prácticas en el alcance</h5>
                <p class="text-secondary mb-3">Este appraisal aún no tiene prácticas seleccionadas en su alcance.</p>
                @can('updateScope', $appraisal)
                    <a href="{{ route('appraisals.scope', $appraisal->id) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-diagram-3 me-1"></i>Definir Alcance CMMI
                    </a>
                @endcan
            </div>
        </div>
    @elseif ($filteredCount === 0)
        <div class="card bg-dark text-white border-secondary shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-funnel fs-1 text-secondary mb-3 d-block"></i>
                <h5 class="fw-bold">No se encontraron prácticas</h5>
                <p class="text-secondary mb-3">Ninguna práctica del alcance coincide con los filtros aplicados.</p>
                <button type="button" wire:click="resetFilters" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar Filtros
                </button>
            </div>
        </div>
    @else
        @foreach ($groupedPractices as $areaId => $practicesInArea)
            @php
                $area = $practicesInArea->first()->practiceArea;
            @endphp
            <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info text-dark fw-bold px-2 py-1 fs-6">{{ $area->code }}</span>
                        <h5 class="mb-0 fw-bold text-white">{{ $area->name }}</h5>
                    </div>
                    <span class="badge bg-secondary bg-opacity-50 text-light">
                        {{ $practicesInArea->count() }} {{ $practicesInArea->count() === 1 ? 'práctica' : 'prácticas' }}
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-secondary small border-secondary">
                                <th style="width: 14%;">Código</th>
                                <th style="width: 38%;">Nombre de la Práctica</th>
                                <th class="text-center" style="width: 10%;">Nivel</th>
                                <th class="text-center" style="width: 14%;">Estado</th>
                                <th class="text-center" style="width: 12%;">% Criterios</th>
                                <th class="text-end" style="width: 12%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($practicesInArea as $practice)
                                @php
                                    $assessment = $practice->assessments->first();
                                    $status = $assessment?->status ?? App\Models\PracticeAssessment::STATUS_NO_EVALUADA;
                                    $badgeColor = App\Models\PracticeAssessment::statusBadgeColor($status);
                                    $percentage = $this->getCompliancePercentage($status);
                                @endphp
                                <tr class="border-secondary">
                                    <td>
                                        <span class="fw-bold font-monospace text-info">{{ $practice->code }}</span>
                                    </td>
                                    <td>
                                        <span class="text-white">{{ $practice->name }}</span>
                                        <div class="text-secondary small mt-1">
                                            <i class="bi bi-check2-circle me-1"></i>{{ $practice->criteria->count() }} {{ $practice->criteria->count() === 1 ? 'criterio' : 'criterios' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">
                                            Nivel {{ $practice->level }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $badgeColor }} px-2 py-1">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($percentage === '0%')
                                            <span class="badge bg-secondary bg-opacity-50 text-white font-monospace">{{ $percentage }}</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-25 text-warning font-monospace small" title="Cálculo detallado pendiente de HU-09/HU-10">
                                                {{ $percentage }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                wire:click="openDetailModal({{ $practice->id }})"
                                                class="btn btn-sm btn-outline-info"
                                                title="Ver criterios, observaciones y evidencias">
                                            <i class="bi bi-eye me-1"></i>Ver detalle
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif

    {{-- Practice Detail Modal (Strictly Read-Only) --}}
    @if ($showDetailModal && $selectedPractice)
        @php
            $modalAssessment = $selectedPractice->assessments->first();
            $modalStatus = $modalAssessment?->status ?? App\Models\PracticeAssessment::STATUS_NO_EVALUADA;
            $modalBadgeColor = App\Models\PracticeAssessment::statusBadgeColor($modalStatus);
            $modalPercentage = $this->getCompliancePercentage($modalStatus);
        @endphp
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.75);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content bg-dark text-white border-secondary shadow-lg">
                    <div class="modal-header border-secondary">
                        <div>
                            <div class="small text-secondary mb-1">
                                {{ $selectedPractice->practiceArea->code }} — {{ $selectedPractice->practiceArea->name }}
                            </div>
                            <h5 class="modal-title fw-bold text-white mb-0">
                                <span class="font-monospace text-info me-2">{{ $selectedPractice->code }}</span>
                                {{ $selectedPractice->name }}
                            </h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeDetailModal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body py-3">
                        {{-- Meta Information Cards --}}
                        <div class="row g-2 mb-4">
                            <div class="col-sm-4">
                                <div class="p-2 bg-secondary bg-opacity-10 rounded border border-secondary text-center">
                                    <div class="text-secondary small">Nivel de Práctica</div>
                                    <div class="fw-bold text-primary">Nivel {{ $selectedPractice->level }}</div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-2 bg-secondary bg-opacity-10 rounded border border-secondary text-center">
                                    <div class="text-secondary small">Estado de Evaluación</div>
                                    <span class="badge {{ $modalBadgeColor }}">{{ $modalStatus }}</span>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-2 bg-secondary bg-opacity-10 rounded border border-secondary text-center">
                                    <div class="text-secondary small">Criterios Cumplidos</div>
                                    <div class="fw-bold text-white font-monospace">{{ $modalPercentage }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Section: Criterios de Aceptación (HU-07) --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-info mb-2">
                                <i class="bi bi-check2-square me-2"></i>Criterios de Aceptación CMMI V3.0
                            </h6>
                            @if ($selectedPractice->criteria->isEmpty())
                                <p class="text-secondary small mb-0">No se encontraron criterios registrados para esta práctica.</p>
                            @else
                                <div class="list-group list-group-flush border-top border-bottom border-secondary">
                                    @foreach ($selectedPractice->criteria as $criterion)
                                        <div class="list-group-item bg-transparent text-white border-secondary px-0 py-2">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <span class="badge bg-secondary bg-opacity-50 text-info font-monospace me-2">{{ $criterion->code }}</span>
                                                    <span>{{ $criterion->description }}</span>
                                                </div>
                                                <span class="badge {{ $criterion->required ? 'bg-primary' : 'bg-secondary' }} text-nowrap">
                                                    {{ $criterion->required ? 'Obligatorio' : 'Opcional' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Section: Observaciones --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-info mb-2">
                                <i class="bi bi-chat-left-text me-2"></i>Observaciones de la Evaluación
                            </h6>
                            <div class="p-3 bg-secondary bg-opacity-10 rounded border border-secondary text-secondary small">
                                <i class="bi bi-info-circle me-1"></i>Sin observaciones registradas.
                            </div>
                        </div>

                        {{-- Section: Evidencias Asociadas --}}
                        <div class="mb-2">
                            <h6 class="fw-bold text-info mb-2">
                                <i class="bi bi-paperclip me-2"></i>Evidencias Asociadas
                            </h6>
                            <div class="p-3 bg-secondary bg-opacity-10 rounded border border-secondary text-secondary small text-center">
                                <i class="bi bi-folder-x fs-3 d-block mb-1 text-secondary"></i>
                                <span>No existen evidencias asociadas.</span>
                                <div class="fst-italic mt-1">El módulo de gestión de evidencias será integrado en HU-12.</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeDetailModal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
