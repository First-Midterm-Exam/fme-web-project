<div class="mb-4">
    @assets
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            window.crearGrafico = function (lienzo, configuracion) {
                Chart.defaults.color = '#adb5bd';
                Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.08)';

                return new Chart(lienzo, configuracion);
            };
        </script>
    @endassets

    @php
        $colores = [
            'No evaluada' => '#6c757d', 'No cumple' => '#dc3545', 'Parcial' => '#ffc107', 'Cumple' => '#0dcaf0', 'Verificada' => '#198754',
            'Registrada' => '#6c757d', 'Verificado' => '#198754', 'Observado' => '#ffc107', 'Rechazado' => '#dc3545',
            'Abierto' => '#ffc107', 'En progreso' => '#0d6efd', 'Resuelto' => '#0dcaf0', 'Cerrado' => '#20c997',
            'Baja' => '#6c757d', 'Media' => '#0dcaf0', 'Alta' => '#fd7e14', 'Crítica' => '#dc3545',
            'Abierta' => '#ffc107', 'Cerrada' => '#198754',
        ];

        $dona = fn (array $datos): array => [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_keys($datos),
                'datasets' => [[
                    'data' => array_values($datos),
                    'backgroundColor' => array_map(fn ($etiqueta) => $colores[$etiqueta] ?? '#6c757d', array_keys($datos)),
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']], 'cutout' => '60%', 'maintainAspectRatio' => false],
        ];

        $barras = fn (array $datos, string $etiqueta, bool $horizontal = false, ?int $maximo = null): array => [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($datos),
                'datasets' => [[
                    'label' => $etiqueta,
                    'data' => array_values($datos),
                    'backgroundColor' => array_map(fn ($clave) => $colores[$clave] ?? '#0dcaf0', array_keys($datos)),
                    'borderRadius' => 4,
                ]],
            ],
            'options' => [
                'indexAxis' => $horizontal ? 'y' : 'x',
                'plugins' => ['legend' => ['display' => false]],
                'scales' => [($horizontal ? 'x' : 'y') => array_filter(['beginAtZero' => true, 'max' => $maximo, 'ticks' => ['precision' => 0]], fn ($valor) => $valor !== null)],
                'maintainAspectRatio' => false,
            ],
        ];
    @endphp

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h2 class="h5 fw-bold mb-0">
                <i class="bi bi-bar-chart-line me-2 text-info"></i>Estado general del appraisal
            </h2>
            @if ($appraisals->isNotEmpty())
                <select wire:model.live="appraisalId" aria-label="Appraisal" class="form-select form-select-sm bg-dark text-white border-secondary" style="max-width: 420px;">
                    @foreach ($appraisals as $option)
                        <option value="{{ $option->id }}">{{ $option->name }} — {{ $option->project->name }} ({{ ucfirst($option->status) }})</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @if (! $stats)
        <div class="card bg-dark text-white border-secondary shadow-sm text-center py-4 mb-4">
            <div class="card-body">
                <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                <p class="text-secondary mb-0">No tienes appraisals disponibles para mostrar estadísticas.</p>
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-secondary small"><i class="bi bi-list-check me-1"></i>Prácticas en el alcance</div>
                        <div class="h3 fw-bold mb-0">{{ $stats['practices']['total'] }}</div>
                        <div class="small text-info">{{ $stats['practices']['average_compliance'] }}% de criterios cumplidos</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-secondary small"><i class="bi bi-paperclip me-1"></i>Evidencias del proyecto</div>
                        <div class="h3 fw-bold mb-0">{{ $stats['evidences']['total'] }}</div>
                        <div class="small text-warning">{{ $stats['evidences']['pending'] }} pendientes de verificar</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-secondary small"><i class="bi bi-exclamation-triangle me-1"></i>Gaps abiertos</div>
                        <div class="h3 fw-bold mb-0 {{ $stats['gaps']['open'] > 0 ? 'text-danger' : '' }}">{{ $stats['gaps']['open'] }}</div>
                        <div class="small text-secondary">{{ $stats['gaps']['closed'] }} cerrados · {{ $stats['gaps']['overdue'] }} vencidos</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-secondary small"><i class="bi bi-tools me-1"></i>Acciones correctivas activas</div>
                        <div class="h3 fw-bold mb-0">{{ $stats['actions']['active'] }}</div>
                        <div class="small text-secondary">{{ $stats['actions']['average_progress'] }}% de avance promedio · {{ $stats['actions']['overdue'] }} vencidas</div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $graficos = [
                [
                    'clave' => 'practicas',
                    'titulo' => 'Prácticas por estado',
                    'icono' => 'bi-list-check',
                    'datos' => $stats['practices']['by_status'],
                    'config' => $dona($stats['practices']['by_status']),
                    'enlace' => route('appraisals.practices', $appraisal->id),
                    'texto' => 'Ver prácticas',
                    'visible' => true,
                ],
                [
                    'clave' => 'areas',
                    'titulo' => 'Cumplimiento por área de práctica (%)',
                    'icono' => 'bi-grid-3x3-gap',
                    'datos' => collect($stats['practices']['by_area'])->mapWithKeys(fn ($area) => [$area['code'] => $area['compliance']])->all(),
                    'config' => $barras(collect($stats['practices']['by_area'])->mapWithKeys(fn ($area) => [$area['code'] => $area['compliance']])->all(), 'Cumplimiento', true, 100),
                    'enlace' => route('readiness.trazabilidad', ['appraisal' => $appraisal->id]),
                    'texto' => 'Ver trazabilidad',
                    'visible' => true,
                ],
                [
                    'clave' => 'evidencias',
                    'titulo' => 'Evidencias por estado',
                    'icono' => 'bi-paperclip',
                    'datos' => $stats['evidences']['by_status'],
                    'config' => $dona($stats['evidences']['by_status']),
                    'enlace' => route('evidencias.verificacion'),
                    'texto' => 'Ir a verificación',
                    'visible' => auth()->user()->can('verificar-evidencias'),
                ],
                [
                    'clave' => 'gaps-estado',
                    'titulo' => 'Gaps por etapa',
                    'icono' => 'bi-exclamation-triangle',
                    'datos' => $stats['gaps']['by_status'],
                    'config' => $barras($stats['gaps']['by_status'], 'Gaps'),
                    'enlace' => route('appraisals.gaps', $appraisal->id),
                    'texto' => 'Ver gaps',
                    'visible' => true,
                ],
                [
                    'clave' => 'gaps-severidad',
                    'titulo' => 'Gaps por severidad',
                    'icono' => 'bi-thermometer-half',
                    'datos' => $stats['gaps']['by_severity'],
                    'config' => $dona($stats['gaps']['by_severity']),
                    'enlace' => route('appraisals.gaps', $appraisal->id),
                    'texto' => 'Ver gaps',
                    'visible' => true,
                ],
                [
                    'clave' => 'acciones',
                    'titulo' => 'Acciones correctivas por estado',
                    'icono' => 'bi-tools',
                    'datos' => $stats['actions']['by_status'],
                    'config' => $dona($stats['actions']['by_status']),
                    'enlace' => route('appraisals.gaps', $appraisal->id),
                    'texto' => 'Ver gaps y acciones',
                    'visible' => true,
                ],
            ];
        @endphp

        <div class="row g-3">
            @foreach ($graficos as $grafico)
                <div class="col-md-6 col-xl-4">
                    <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                        <div class="card-header bg-dark border-secondary py-2 d-flex justify-content-between align-items-center">
                            <h3 class="h6 fw-bold mb-0"><i class="bi {{ $grafico['icono'] }} me-2 text-info"></i>{{ $grafico['titulo'] }}</h3>
                            @if ($grafico['visible'])
                                <a href="{{ $grafico['enlace'] }}" class="small text-info text-decoration-none" wire:navigate>{{ $grafico['texto'] }} <i class="bi bi-arrow-right"></i></a>
                            @endif
                        </div>
                        <div class="card-body">
                            @if (array_sum($grafico['datos']) === 0)
                                <p class="text-secondary small text-center my-5">Sin datos registrados.</p>
                            @else
                                <div wire:key="grafico-{{ $appraisal->id }}-{{ $grafico['clave'] }}"
                                     x-data
                                     x-init="crearGrafico($refs.lienzo, @js($grafico['config']))"
                                     style="position: relative; height: 220px;">
                                    <canvas x-ref="lienzo" aria-label="{{ $grafico['titulo'] }}" role="img"></canvas>
                                </div>
                            @endif
                            <ul class="list-unstyled small mb-0 mt-3">
                                @foreach ($grafico['datos'] as $etiqueta => $valor)
                                    <li class="d-flex justify-content-between border-bottom border-secondary border-opacity-25 py-1">
                                        <span><span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background-color: {{ $colores[$etiqueta] ?? '#0dcaf0' }};"></span>{{ $etiqueta }}</span>
                                        <span class="fw-semibold">{{ $valor }}{{ $grafico['clave'] === 'areas' ? '%' : '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
