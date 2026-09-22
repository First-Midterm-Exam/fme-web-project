<x-app-layout>
    <x-slot name="header">Simulación de Appraisal</x-slot>

    @php
        $colores = [
            App\Models\ReadinessMeasurement::STATUS_LISTO => 'success',
            App\Models\ReadinessMeasurement::STATUS_LISTO_CON_CONDICIONES => 'warning',
            App\Models\ReadinessMeasurement::STATUS_NO_LISTO => 'danger',
        ];
    @endphp

    <div x-data="{
            nivelObjetivo: @js($resultado['nivel'] ?? $appraisal->target_level),
            resultado: @js($resultado),
            colores: @js($colores),
            cargando: false,
            error: null,
            async realizarSimulacion() {
                this.cargando = true;
                this.error = null;

                try {
                    const respuesta = await fetch(@js(route('appraisals.simulaciones.store', $appraisal)), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        },
                        body: JSON.stringify({ nivelObjetivo: Number(this.nivelObjetivo) }),
                    });
                    const datos = await respuesta.json();

                    if (! respuesta.ok) {
                        this.error = datos.errors?.nivelObjetivo?.[0] ?? datos.message ?? 'No se pudo ejecutar la simulación.';

                        return;
                    }

                    this.mostrarResultadoSimulacion(datos.resultadoSimulacion);
                } catch (e) {
                    this.error = 'No se pudo ejecutar la simulación.';
                } finally {
                    this.cargando = false;
                }
            },
            mostrarResultadoSimulacion(resultado) {
                this.resultado = resultado;

                const fila = document.createElement('tr');
                const celdas = [resultado.generado_en, 'Nivel ' + resultado.nivel, resultado.score + '%', resultado.readiness, resultado.brechas.length, @js(auth()->user()->name)];

                celdas.forEach((valor, posicion) => {
                    const celda = document.createElement('td');
                    const contenido = posicion === 3 ? document.createElement('span') : celda;

                    contenido.textContent = valor;

                    if (posicion === 3) {
                        contenido.className = 'badge text-bg-' + this.colores[resultado.readiness];
                        celda.appendChild(contenido);
                    }

                    fila.appendChild(celda);
                });

                this.$refs.historial.prepend(fila);
                this.$refs.sinHistorial?.remove();
            },
        }">
        <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
            <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">{{ $appraisal->name }}</h2>
                    <p class="text-secondary small mb-0">
                        Proyecto: <span class="text-light">{{ $appraisal->project->name }}</span>
                        · Nivel objetivo del appraisal: <span class="text-light">{{ $appraisal->target_level }}</span>
                        · Estado: <span class="badge {{ $appraisal->statusBadgeColor() }}">{{ ucfirst($appraisal->status) }}</span>
                    </p>
                </div>

                @can('simulate', $appraisal)
                    <form class="d-flex align-items-end gap-2" x-on:submit.prevent="realizarSimulacion()">
                        <div>
                            <label for="nivelObjetivo" class="form-label small text-secondary mb-1">Nivel a simular</label>
                            <select id="nivelObjetivo" x-model="nivelObjetivo" class="form-select form-select-sm bg-dark text-white border-secondary">
                                @foreach (range(1, 5) as $nivel)
                                    <option value="{{ $nivel }}">Nivel {{ $nivel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm" x-bind:disabled="cargando">
                            <span x-show="cargando" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <i x-show="! cargando" class="bi bi-play-circle me-1"></i>Ejecutar simulación
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div x-show="error" style="display: none;" class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-octagon me-2"></i><span x-text="error"></span>
        </div>

        <div class="alert alert-secondary small" role="note">
            <i class="bi bi-info-circle me-2"></i>{{ App\Services\AppraisalReadinessService::DISCLAIMER_TEXT }}
        </div>

        <template x-if="! resultado">
            <div class="card bg-dark text-white border-secondary shadow-sm text-center py-4 mb-4">
                <div class="card-body">
                    <i class="bi bi-clipboard2-pulse fs-2 text-secondary d-block mb-2"></i>
                    <p class="text-secondary mb-0">Aún no se ha ejecutado ninguna simulación para este appraisal.</p>
                </div>
            </div>
        </template>

        <template x-if="resultado">
            <div class="mb-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="card bg-dark text-white shadow-sm h-100" x-bind:class="'border-' + colores[resultado.readiness]">
                            <div class="card-body text-center">
                                <div class="text-secondary small mb-1">Veredicto</div>
                                <div class="h4 fw-bold mb-1" x-bind:class="'text-' + colores[resultado.readiness]" x-text="resultado.readiness"></div>
                                <div class="small text-secondary">Nivel <span x-text="resultado.nivel"></span> · <span x-text="resultado.generado_en"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-secondary small mb-1">Score simulado</div>
                                <div class="display-6 fw-bold" x-text="resultado.score + '%'"></div>
                                <div class="small text-secondary">
                                    <span x-text="resultado.desglose.practicas_cumplen"></span> de <span x-text="resultado.desglose.practicas_evaluadas"></span> prácticas cumplen
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark text-white border-secondary shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-secondary small mb-1">Brechas</div>
                                <div class="display-6 fw-bold" x-text="resultado.brechas.length"></div>
                                <div class="small text-secondary">
                                    <span x-text="resultado.desglose.brechas_bloqueantes"></span> bloqueantes · <span x-text="resultado.desglose.gaps_criticos"></span> gaps críticos
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-dark text-white border-secondary shadow-sm">
                    <div class="card-header bg-dark border-secondary py-2">
                        <h3 class="h6 fw-bold mb-0">
                            <i class="bi bi-list-task me-2 text-info"></i>
                            <span x-text="resultado.readiness === '{{ App\Models\ReadinessMeasurement::STATUS_LISTO_CON_CONDICIONES }}' ? 'Condiciones a cumplir' : 'Brechas identificadas'"></span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <p x-show="resultado.brechas.length === 0" class="text-success small text-center my-4">
                            <i class="bi bi-check-circle me-1"></i>No se identificaron brechas para el nivel simulado.
                        </p>
                        <div x-show="resultado.brechas.length > 0" class="table-responsive">
                            <table class="table table-dark table-hover align-middle mb-0 small">
                                <thead>
                                    <tr>
                                        <th>Área</th>
                                        <th>Práctica</th>
                                        <th>Tipo</th>
                                        <th>Detalle</th>
                                        <th class="text-center">Bloqueante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(brecha, indice) in resultado.brechas" x-bind:key="indice">
                                        <tr>
                                            <td x-text="brecha.area"></td>
                                            <td>
                                                <span class="fw-semibold" x-text="brecha.practica"></span>
                                                <div class="text-secondary" x-text="brecha.practica_nombre"></div>
                                            </td>
                                            <td>
                                                <span class="badge text-bg-secondary" x-text="brecha.tipo"></span>
                                                <span x-show="brecha.severidad" class="badge text-bg-warning" x-text="brecha.severidad"></span>
                                            </td>
                                            <td x-text="brecha.detalle"></td>
                                            <td class="text-center">
                                                <i class="bi" x-bind:class="brecha.bloqueante ? 'bi-x-circle-fill text-danger' : 'bi-dash-circle text-warning'"></i>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="card bg-dark text-white border-secondary shadow-sm">
            <div class="card-header bg-dark border-secondary py-2">
                <h3 class="h6 fw-bold mb-0"><i class="bi bi-clock-history me-2 text-info"></i>Historial de simulaciones</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0 small">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nivel</th>
                                <th>Score</th>
                                <th>Veredicto</th>
                                <th>Brechas</th>
                                <th>Ejecutada por</th>
                            </tr>
                        </thead>
                        <tbody x-ref="historial">
                            @forelse ($simulaciones as $simulacion)
                                @php $estado = $simulacion->readinessMeasurement?->status ?? App\Models\ReadinessMeasurement::STATUS_NO_LISTO; @endphp
                                <tr>
                                    <td>{{ $simulacion->created_at?->format('d/m/Y H:i') }}</td>
                                    <td>Nivel {{ $simulacion->target_level }}</td>
                                    <td>{{ $simulacion->score }}%</td>
                                    <td><span class="badge text-bg-{{ $colores[$estado] ?? 'secondary' }}">{{ $estado }}</span></td>
                                    <td>{{ count($simulacion->gaps_found ?? []) }}</td>
                                    <td>{{ $simulacion->executor?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr x-ref="sinHistorial">
                                    <td colspan="6" class="text-center text-secondary py-4">Sin simulaciones registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
