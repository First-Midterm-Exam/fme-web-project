<x-app-layout>
    <x-slot name="header">Panel Principal</x-slot>

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 text-primary rounded-circle p-3 d-inline-flex">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <div>
                    <h2 class="h5 fw-bold mb-1">Bienvenido, {{ auth()->user()->name }}</h2>
                    <p class="text-secondary mb-0 small">
                        Plataforma de preparación para el appraisal CMMI V3.0 de DIMA LTDA.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @can('ver-readiness')
        <livewire:dashboard.statistics />
    @endcan

    @php
        $visibleAppraisals = App\Models\Appraisal::visibleFor(auth()->user())->get();
    @endphp

    @if ($visibleAppraisals->isNotEmpty())
        <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                            <h3 class="h6 fw-bold mb-0">Exportar Reportes PDF</h3>
                        </div>
                        @if ($visibleAppraisals->count() === 1)
                            <p class="text-secondary small mb-0">
                                Appraisal: <span class="text-light fw-semibold">{{ $visibleAppraisals->first()->name }}</span>
                            </p>
                        @else
                            <p class="text-secondary small mb-0">
                                Tienes múltiples appraisals visibles. Elige uno para exportar sus reportes detallados.
                            </p>
                        @endif
                    </div>

                    @if ($visibleAppraisals->count() === 1)
                        @php $appId = $visibleAppraisals->first()->id; @endphp
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('appraisals.reportes.export', [$appId, 'practicas']) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="Reporte de Prácticas">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Prácticas
                            </a>
                            <a href="{{ route('appraisals.reportes.export', [$appId, 'evidencias']) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="Reporte de Evidencias">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Evidencias
                            </a>
                            <a href="{{ route('appraisals.reportes.export', [$appId, 'gaps']) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="Reporte de Gaps">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Gaps
                            </a>
                            <a href="{{ route('appraisals.reportes.export', [$appId, 'acciones']) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="Reporte de Acciones Correctivas">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Acciones
                            </a>
                            <a href="{{ route('appraisals.reportes.export', [$appId, 'readiness']) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="Reporte de Readiness">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Readiness
                            </a>
                        </div>
                    @else
                        <div>
                            <a href="{{ route('appraisals.index') }}" class="btn btn-danger btn-sm">
                                <i class="bi bi-arrow-right-circle me-1"></i>Seleccionar Appraisal
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        @foreach (App\Support\Modulos::items() as $modulo)
            @can($modulo['capacidad'])
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route($modulo['ruta']) }}" class="card h-100 bg-dark text-white border-secondary shadow-sm text-decoration-none" wire:navigate>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi {{ $modulo['icono'] }} text-primary fs-5"></i>
                                <h3 class="h6 fw-bold mb-0">{{ $modulo['etiqueta'] }}</h3>
                            </div>
                            <p class="text-secondary small mb-0">{{ $modulo['descripcion'] }}</p>
                        </div>
                    </a>
                </div>
            @endcan
        @endforeach
    </div>
</x-app-layout>
