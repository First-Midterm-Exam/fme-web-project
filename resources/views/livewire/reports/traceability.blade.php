<div>
    <div class="mb-4">
        <h1 class="h3 text-white fw-bold mb-1">
            <i class="bi bi-bezier2 me-2 text-info"></i>Trazabilidad de Prácticas
        </h1>
        <p class="text-secondary small mb-0">
            Cadena completa de cada práctica: criterio → evidencia → verificación → gap → acción correctiva.
        </p>
    </div>

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label for="appraisal" class="form-label text-secondary small mb-1">
                        <i class="bi bi-clipboard-check me-1"></i>Appraisal
                    </label>
                    <select id="appraisal" wire:model.live="appraisalId" class="form-select form-select-sm bg-dark text-white border-secondary">
                        @forelse ($appraisals as $option)
                            <option value="{{ $option->id }}">{{ $option->name }} — {{ $option->project->name }} ({{ ucfirst($option->status) }})</option>
                        @empty
                            <option value="">No tienes appraisals disponibles</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="buscar" class="form-label text-secondary small mb-1">
                        <i class="bi bi-search me-1"></i>Buscar práctica
                    </label>
                    <input id="buscar" type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Código o nombre...">
                </div>
            </div>
        </div>
    </div>

    @if (! $appraisal)
        <div class="card bg-dark text-white border-secondary shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-inbox fs-1 text-secondary mb-3 d-block"></i>
                <h5 class="fw-bold">Sin appraisals para consultar</h5>
                <p class="text-secondary mb-0">No tienes appraisals disponibles en tus proyectos.</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card bg-dark text-white border-secondary shadow-sm">
                    <div class="card-header bg-dark border-secondary py-3">
                        <h2 class="h6 mb-0 fw-bold">
                            <i class="bi bi-list-check me-2 text-info"></i>Prácticas del alcance
                            <span class="badge bg-secondary bg-opacity-50 text-light ms-2">{{ $summary->count() }}</span>
                        </h2>
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 70vh; overflow-y: auto;">
                        @forelse ($summary as $row)
                            <button type="button"
                                    wire:click="selectPractice({{ $row['practice']->id }})"
                                    class="list-group-item list-group-item-action bg-dark text-white border-secondary py-2 {{ $chain && $chain['practice']->id === $row['practice']->id ? 'border-start border-4 border-info bg-info bg-opacity-10' : '' }}">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <span class="font-monospace text-info small">{{ $row['practice']->code }}</span>
                                        <div class="small">{{ $row['practice']->name }}</div>
                                    </div>
                                    <span class="badge {{ App\Models\PracticeEvaluation::statusBadgeColor($row['status']) }} text-nowrap">{{ $row['status'] }}</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-1 small text-secondary">
                                    <span title="Criterios cumplidos"><i class="bi bi-check2-square me-1"></i>{{ $row['compliance'] }}%</span>
                                    <span title="Evidencias verificadas / asociadas"><i class="bi bi-paperclip me-1"></i>{{ $row['verified_evidences'] }}/{{ $row['evidences'] }}</span>
                                    <span title="Gaps abiertos" class="{{ $row['open_gaps'] > 0 ? 'text-danger' : '' }}"><i class="bi bi-exclamation-triangle me-1"></i>{{ $row['open_gaps'] }}</span>
                                    <span title="Acciones correctivas activas"><i class="bi bi-tools me-1"></i>{{ $row['active_actions'] }}</span>
                                </div>
                            </button>
                        @empty
                            <div class="list-group-item bg-dark text-secondary border-secondary text-center py-4 small">
                                No hay prácticas que coincidan.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                @if (! $chain)
                    <div class="card bg-dark text-white border-secondary shadow-sm text-center py-5">
                        <div class="card-body">
                            <i class="bi bi-bezier2 fs-1 text-secondary mb-3 d-block"></i>
                            <h5 class="fw-bold">Selecciona una práctica</h5>
                            <p class="text-secondary mb-0">Elige una práctica de la lista para ver su cadena completa de trazabilidad.</p>
                        </div>
                    </div>
                @else
                    <div class="card bg-dark text-white border-secondary shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="small text-secondary">{{ $chain['practice']->practiceArea->code }} — {{ $chain['practice']->practiceArea->name }}</div>
                                    <h2 class="h5 fw-bold mb-1">
                                        <span class="font-monospace text-info me-2">{{ $chain['practice']->code }}</span>{{ $chain['practice']->name }}
                                    </h2>
                                    <div class="d-flex flex-wrap gap-2 small">
                                        <span class="badge bg-primary bg-opacity-25 text-primary">Nivel {{ $chain['practice']->level }}</span>
                                        @php
                                            $practiceStatus = $chain['evaluation']?->status ?? App\Models\PracticeEvaluation::STATUS_NO_EVALUADA;
                                        @endphp
                                        <span class="badge {{ App\Models\PracticeEvaluation::statusBadgeColor($practiceStatus) }}">{{ $practiceStatus }}</span>
                                        <span class="badge bg-secondary">{{ $chain['gaps_total'] }} {{ $chain['gaps_total'] === 1 ? 'gap' : 'gaps' }}</span>
                                        <span class="badge bg-secondary">{{ $chain['actions_total'] }} {{ $chain['actions_total'] === 1 ? 'acción' : 'acciones' }}</span>
                                    </div>
                                </div>
                                @can('evaluate', $appraisal)
                                    <a href="{{ route('appraisals.practices.checklist', [$appraisal->id, $chain['practice']->id]) }}" class="btn btn-outline-primary btn-sm" wire:navigate>
                                        <i class="bi bi-ui-checks me-1"></i>Ir al checklist
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="card bg-dark text-white border-secondary shadow-sm mb-3">
                        <div class="card-header bg-dark border-secondary py-3">
                            <h3 class="h6 mb-0 fw-bold">
                                <i class="bi bi-check2-square me-2 text-info"></i>Criterios → Gaps → Acciones
                            </h3>
                        </div>
                        <ul class="list-group list-group-flush">
                            @forelse ($chain['criteria'] as $item)
                                @php
                                    $mark = $item['check']?->status ?? App\Models\CriterionCheck::STATUS_PENDIENTE;
                                @endphp
                                <li class="list-group-item bg-dark text-white border-secondary py-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="badge bg-secondary bg-opacity-50 text-info font-monospace">{{ $item['criterion']->code }}</span>
                                        <span class="badge {{ $item['criterion']->required ? 'bg-primary' : 'bg-secondary' }}">{{ $item['criterion']->required ? 'Obligatorio' : 'Opcional' }}</span>
                                        <span class="badge {{ App\Models\CriterionCheck::statusBadgeColor($mark) }}">{{ $mark }}</span>
                                    </div>
                                    <p class="mb-1 small">{{ $item['criterion']->description }}</p>
                                    @if ($item['check']?->notes)
                                        <div class="small text-secondary"><i class="bi bi-chat-left-text me-1"></i>{{ $item['check']->notes }}</div>
                                    @endif
                                    @foreach ($item['gaps'] as $gap)
                                        @include('livewire.reports.partials.gap-chain', ['gap' => $gap])
                                    @endforeach
                                </li>
                            @empty
                                <li class="list-group-item bg-dark text-secondary border-secondary text-center py-3 small">
                                    La práctica no tiene criterios activos.
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="card bg-dark text-white border-secondary shadow-sm mb-3">
                        <div class="card-header bg-dark border-secondary py-3">
                            <h3 class="h6 mb-0 fw-bold">
                                <i class="bi bi-paperclip me-2 text-info"></i>Evidencias → Verificación → Gaps → Acciones
                            </h3>
                        </div>
                        <ul class="list-group list-group-flush">
                            @forelse ($chain['evidences'] as $item)
                                <li class="list-group-item bg-dark text-white border-secondary py-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        @can('view', $item['evidence'])
                                            <a href="{{ route('evidencias.show', $item['evidence']) }}" class="badge bg-secondary bg-opacity-50 text-info font-monospace text-decoration-none" wire:navigate>{{ $item['evidence']->code }}</a>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-50 text-info font-monospace">{{ $item['evidence']->code }}</span>
                                        @endcan
                                        <span class="small">{{ $item['evidence']->name }}</span>
                                        @if ($item['evidence']->currentVersion)
                                            <span class="badge bg-info text-dark">v{{ $item['evidence']->currentVersion->number }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-secondary">
                                        <i class="bi bi-patch-check me-1"></i>Verificación:
                                        <span class="badge bg-secondary bg-opacity-50 text-white">{{ $item['evidence']->status->name }}</span>
                                        @if ($item['evidence']->verifiedBy)
                                            por {{ $item['evidence']->verifiedBy->name }}
                                            el {{ $item['evidence']->verified_at?->format('d/m/Y H:i') }}
                                        @endif
                                    </div>
                                    @if ($item['evidence']->verification_reason)
                                        <div class="small text-secondary"><i class="bi bi-chat-left-text me-1"></i>{{ $item['evidence']->verification_reason }}</div>
                                    @endif
                                    @foreach ($item['gaps'] as $gap)
                                        @include('livewire.reports.partials.gap-chain', ['gap' => $gap])
                                    @endforeach
                                </li>
                            @empty
                                <li class="list-group-item bg-dark text-secondary border-secondary text-center py-3 small">
                                    No hay evidencias del proyecto asociadas a esta práctica.
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    @if ($chain['other_gaps']->isNotEmpty())
                        <div class="card bg-dark text-white border-secondary shadow-sm">
                            <div class="card-header bg-dark border-secondary py-3">
                                <h3 class="h6 mb-0 fw-bold">
                                    <i class="bi bi-exclamation-triangle me-2 text-info"></i>Otros gaps de la práctica
                                </h3>
                            </div>
                            <div class="card-body">
                                @foreach ($chain['other_gaps'] as $gap)
                                    @include('livewire.reports.partials.gap-chain', ['gap' => $gap])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
