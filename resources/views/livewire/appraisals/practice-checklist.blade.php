<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('appraisals.index') }}" class="text-decoration-none text-info">Appraisals</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('appraisals.practices', $appraisal->id) }}" class="text-decoration-none text-info">{{ $appraisal->name }}</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $practice->code }}</li>
                </ol>
            </nav>
            <h1 class="h3 text-white fw-bold mb-1">
                <i class="bi bi-ui-checks me-2 text-info"></i>Checklist de Criterios
            </h1>
            <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                <span><strong class="text-white">{{ $practice->practiceArea->code }}</strong> — {{ $practice->practiceArea->name }}</span>
                <span>•</span>
                <span><strong class="text-white">Nivel:</strong> {{ $practice->level }}</span>
                <span>•</span>
                <span class="badge {{ $appraisal->statusBadgeColor() }}">{{ ucfirst($appraisal->status) }}</span>
                @cannot('evaluate', $appraisal)
                    <span>•</span>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary">
                        <i class="bi bi-eye me-1"></i>Modo Consulta
                    </span>
                @endcannot
            </div>
        </div>

        <div class="d-flex gap-2">
            @can('evaluar-cumplimiento')
                <a href="{{ route('criterios.index', [$appraisal->id, $practice->id]) }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-rulers me-1"></i>Administrar criterios
                </a>
            @endcan
            <a href="{{ route('appraisals.practices', $appraisal->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Prácticas
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <span class="badge bg-info text-dark fw-bold fs-6 px-2 py-1 font-monospace">{{ $practice->code }}</span>
                <div class="flex-grow-1">
                    <h2 class="h5 fw-bold mb-1">{{ $practice->name }}</h2>
                    <div class="text-secondary small">
                        Estado actual de la práctica:
                        <span class="badge {{ App\Models\PracticeEvaluation::statusBadgeColor($evaluation->status) }}">{{ $evaluation->status }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Criterios Aplicables</div>
                    <div class="h4 fw-bold mb-0 text-white">{{ $applicableCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Criterios Cumplidos</div>
                    <div class="h4 fw-bold mb-0 text-success">{{ $metCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Sin Marcar</div>
                    <div class="h4 fw-bold mb-0 text-warning">
                        {{ collect($marks)->filter(fn ($m) => $m === App\Models\CriterionCheck::STATUS_PENDIENTE)->count() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-body py-3">
                    <div class="text-secondary small">Cumplimiento</div>
                    <div class="h4 fw-bold mb-0 text-info font-monospace">{{ $percentage }}%</div>
                    <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-info" style="width: {{ $percentage }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('evaluate', $appraisal)
        @if ($criteria->isNotEmpty())
            <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
                <div class="card-body py-3 d-flex flex-wrap align-items-center gap-2">
                    <span class="text-secondary small me-2"><i class="bi bi-lightning-charge me-1"></i>Marcar todos los criterios:</span>
                    @foreach ($statuses as $status)
                        <button type="button"
                                wire:click="markAll('{{ $status }}')"
                                wire:confirm="¿Marcar los {{ $criteria->count() }} criterios como «{{ $status }}»?"
                                class="btn btn-sm btn-outline-light">
                            {{ $status }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endcan

    @if ($criteria->isEmpty())
        <div class="card bg-dark text-white border-secondary shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-inbox fs-1 text-secondary mb-3 d-block"></i>
                <h5 class="fw-bold">Sin criterios activos</h5>
                <p class="text-secondary mb-0">Esta práctica no tiene criterios de evaluación activos para marcar.</p>
            </div>
        </div>
    @else
        <div class="card bg-dark text-white border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-3">
                <h2 class="h6 mb-0 fw-bold">
                    <i class="bi bi-check2-square me-2 text-info"></i>Criterios de Evaluación
                    <span class="badge bg-secondary bg-opacity-50 text-light ms-2">{{ $criteria->count() }}</span>
                </h2>
            </div>

            <ul class="list-group list-group-flush">
                @foreach ($criteria as $criterion)
                    @php
                        $mark = $marks[$criterion->id] ?? App\Models\CriterionCheck::STATUS_PENDIENTE;
                    @endphp
                    <li class="list-group-item bg-dark text-white border-secondary py-3">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-7">
                                <div class="d-flex align-items-start gap-2 mb-1">
                                    <span class="badge bg-secondary bg-opacity-50 text-info font-monospace">{{ $criterion->code }}</span>
                                    <span class="badge {{ $criterion->required ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $criterion->required ? 'Obligatorio' : 'Opcional' }}
                                    </span>
                                    <span class="badge {{ App\Models\CriterionCheck::statusBadgeColor($mark) }}">{{ $mark }}</span>
                                </div>
                                <p class="mb-1">{{ $criterion->description }}</p>

                                @if ($editingNoteFor === $criterion->id)
                                    <div class="mt-2">
                                        <label for="nota-{{ $criterion->id }}" class="form-label text-secondary small mb-1">Observación</label>
                                        <textarea id="nota-{{ $criterion->id }}"
                                                  wire:model="notes.{{ $criterion->id }}"
                                                  rows="3"
                                                  maxlength="1000"
                                                  class="form-control form-control-sm bg-dark text-white border-secondary @error('notes.'.$criterion->id) is-invalid @enderror"
                                                  placeholder="Registre la justificación de esta marca..."></textarea>
                                        @error('notes.'.$criterion->id)
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="d-flex gap-2 mt-2">
                                            <button type="button" wire:click="saveNote({{ $criterion->id }})" class="btn btn-sm btn-primary">
                                                <i class="bi bi-save me-1"></i>Guardar observación
                                            </button>
                                            <button type="button" wire:click="cancelNote" class="btn btn-sm btn-outline-secondary">Cancelar</button>
                                        </div>
                                    </div>
                                @elseif (! empty($notes[$criterion->id]))
                                    <div class="mt-2 p-2 bg-secondary bg-opacity-10 rounded border border-secondary small text-secondary">
                                        <i class="bi bi-chat-left-text me-1"></i>{{ $notes[$criterion->id] }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-lg-5 text-lg-end">
                                @can('evaluate', $appraisal)
                                    <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Marcar criterio {{ $criterion->code }}">
                                        @foreach ($statuses as $status)
                                            <button type="button"
                                                    wire:click="markCriterion({{ $criterion->id }}, '{{ $status }}')"
                                                    class="btn {{ $mark === $status ? 'btn-info' : 'btn-outline-info' }}">
                                                {{ $status }}
                                            </button>
                                        @endforeach
                                    </div>

                                    @if ($editingNoteFor !== $criterion->id)
                                        <div class="mt-2">
                                            <button type="button" wire:click="editNote({{ $criterion->id }})" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-chat-left-text me-1"></i>{{ empty($notes[$criterion->id]) ? 'Agregar observación' : 'Editar observación' }}
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <span class="badge {{ App\Models\CriterionCheck::statusBadgeColor($mark) }} fs-6">{{ $mark }}</span>
                                @endcan
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
