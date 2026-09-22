<div class="border-start border-2 border-danger border-opacity-50 ps-3 mt-2">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <i class="bi bi-arrow-return-right text-secondary"></i>
        @can('view', $gap)
            <a href="{{ route('gaps.show', $gap) }}" class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 text-decoration-none font-monospace" wire:navigate>{{ $gap->code }}</a>
        @else
            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 font-monospace">{{ $gap->code }}</span>
        @endcan
        <span class="small">{{ $gap->title }}</span>
        <span class="badge {{ $gap->statusBadgeColor() }}">{{ $gap->status }}</span>
        @if ($gap->severity)
            <span class="badge {{ $gap->severityBadgeColor() }}">{{ $gap->severity }}</span>
        @endif
        @if ($gap->isOverdue())
            <span class="badge bg-danger"><i class="bi bi-alarm me-1"></i>Vencido</span>
        @endif
    </div>
    <div class="small text-secondary mt-1">
        Responsable: {{ $gap->assignedTo?->name ?? 'Sin asignar' }}
        · Fecha límite: {{ $gap->due_date ? \Illuminate\Support\Carbon::parse($gap->due_date)->format('d/m/Y') : 'Sin definir' }}
    </div>

    @forelse ($gap->correctiveActions as $action)
        <div class="ms-3 mt-2 p-2 rounded bg-secondary bg-opacity-10 border border-secondary">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <i class="bi bi-tools text-info"></i>
                <span class="small text-white">{{ $action->description }}</span>
                <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $action->status)) }}</span>
                @if ($action->isOverdue())
                    <span class="badge bg-danger"><i class="bi bi-alarm me-1"></i>Vencida</span>
                @endif
            </div>
            <div class="small text-secondary mt-1">
                Responsable: {{ $action->responsible?->name ?? 'Sin asignar' }}
                · Fecha límite: {{ $action->due_date ? \Illuminate\Support\Carbon::parse($action->due_date)->format('d/m/Y') : 'Sin definir' }}
                @if ($action->solutionEvidence)
                    · Evidencia de solución:
                    @can('view', $action->solutionEvidence)
                        <a href="{{ route('evidencias.show', $action->solutionEvidence) }}" class="text-info" wire:navigate>{{ $action->solutionEvidence->code }}</a>
                    @else
                        {{ $action->solutionEvidence->code }}
                    @endcan
                @endif
            </div>
            <div class="progress mt-1" style="height: 5px;" role="progressbar" aria-valuenow="{{ $action->progress_percent }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar {{ $action->progress_percent >= 100 ? 'bg-success' : 'bg-info' }}" style="width: {{ $action->progress_percent }}%;"></div>
            </div>
        </div>
    @empty
        <div class="ms-3 mt-1 small text-secondary fst-italic">
            <i class="bi bi-dash me-1"></i>Sin acción correctiva registrada.
        </div>
    @endforelse
</div>
