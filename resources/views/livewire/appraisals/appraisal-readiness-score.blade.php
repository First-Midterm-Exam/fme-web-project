<div class="p-6 max-w-7xl mx-auto space-y-6 text-gray-100">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Appraisal Readiness Score</h1>
            <p class="text-sm text-gray-400 mt-1">
                Appraisal: <span class="text-indigo-400 font-medium">{{ $appraisal->name }}</span> | 
                Proyecto: <span class="text-gray-200 font-medium">{{ $appraisal->project->name ?? 'N/A' }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('appraisals.reportes.export', [$appraisal->id, 'readiness']) }}" class="px-4 py-2 text-sm font-medium text-white bg-red-700 hover:bg-red-800 rounded-lg transition-colors inline-block" target="_blank">
                📄 Exportar PDF
            </a>
            <a href="{{ route('appraisals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors inline-block">
                &larr; Volver
            </a>
        </div>
    </div>

    <div style="background-color: rgba(69, 26, 3, 0.4); border: 1px solid rgba(217, 119, 6, 0.4); border-radius: 0.75rem; padding: 1rem; margin-top: 1rem; color: #fef3c7;">
        <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
            <svg style="width: 24px; height: 24px; min-width: 24px; max-width: 24px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div style="font-size: 0.825rem; line-height: 1.4;">
                <strong style="display: block; text-transform: uppercase; color: #fbbf24; margin-bottom: 0.25rem; letter-spacing: 0.05em;">Aviso Legal y de Certificación</strong>
                {{ $disclaimer }}
            </div>
        </div>
    </div>

    <!-- Grid de Métricas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
        
        <!-- Score Global -->
        <div style="background-color: #111827; border: 1px solid #1f2937; border-radius: 1rem; padding: 1.5rem; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <span style="font-size: 0.75rem; text-transform: uppercase; color: #9ca3af; font-weight: 600; letter-spacing: 0.05em;">Score Global</span>
            <div style="font-size: 3rem; font-weight: 900; margin: 0.5rem 0; color: {{ $score >= 80 ? '#34d399' : ($score >= 50 ? '#fbbf24' : '#f87171') }};">
                {{ $score }}%
            </div>
            <span style="font-size: 0.75rem; color: #6b7280;">
                {{ $score >= 80 ? 'Preparación Alta' : ($score >= 50 ? 'Preparación Media' : 'Preparación Baja') }}
            </span>
        </div>

        <!-- Prácticas (40%) -->
        <div style="background-color: #111827; border: 1px solid #1f2937; border-radius: 1rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.5rem;">
                    <span>Prácticas Evaluadas</span>
                    <span style="color: #818cf8; font-weight: 600;">{{ $breakdown['practices']['weight'] }}% Peso</span>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #ffffff;">
                    {{ $breakdown['practices']['count'] }} / {{ $breakdown['practices']['total'] }}
                </div>
                <div style="width: 100%; background-color: #1f2937; height: 8px; border-radius: 9999px; margin-top: 0.75rem; overflow: hidden;">
                    <div style="background-color: #6366f1; height: 100%; width: {{ $breakdown['practices']['completion_rate'] }}%; border-radius: 9999px;"></div>
                </div>
            </div>
            <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 1rem; border-top: 1px solid #1f2937; padding-top: 0.5rem; display: flex; justify-content: space-between;">
                <span>Aporte:</span>
                <strong style="color: #ffffff;">+{{ $breakdown['practices']['contribution'] }}%</strong>
            </div>
        </div>

        <!-- Evidencias (40%) -->
        <div style="background-color: #111827; border: 1px solid #1f2937; border-radius: 1rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.5rem;">
                    <span>Evidencias Verificadas</span>
                    <span style="color: #22d3ee; font-weight: 600;">{{ $breakdown['evidences']['weight'] }}% Peso</span>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #ffffff;">
                    {{ $breakdown['evidences']['count'] }} / {{ $breakdown['evidences']['required'] }}
                </div>
                <div style="width: 100%; background-color: #1f2937; height: 8px; border-radius: 9999px; margin-top: 0.75rem; overflow: hidden;">
                    <div style="background-color: #06b6d4; height: 100%; width: {{ $breakdown['evidences']['completion_rate'] }}%; border-radius: 9999px;"></div>
                </div>
            </div>
            <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 1rem; border-top: 1px solid #1f2937; padding-top: 0.5rem; display: flex; justify-content: space-between;">
                <span>Aporte:</span>
                <strong style="color: #ffffff;">+{{ $breakdown['evidences']['contribution'] }}%</strong>
            </div>
        </div>

        <!-- Gaps (20%) -->
        <div style="background-color: #111827; border: 1px solid #1f2937; border-radius: 1rem; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.5rem;">
                    <span>Salud de Gaps</span>
                    <span style="color: #34d399; font-weight: 600;">{{ $breakdown['gaps']['weight'] }}% Peso</span>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: {{ $breakdown['gaps']['open_count'] > 0 ? '#fbbf24' : '#34d399' }};">
                    {{ $breakdown['gaps']['open_count'] }} abiertos
                </div>
                <div style="width: 100%; background-color: #1f2937; height: 8px; border-radius: 9999px; margin-top: 0.75rem; overflow: hidden;">
                    <div style="background-color: #10b981; height: 100%; width: {{ $breakdown['gaps']['health_rate'] }}%; border-radius: 9999px;"></div>
                </div>
            </div>
            <div style="font-size: 0.75rem; color: #9ca3af; margin-top: 1rem; border-top: 1px solid #1f2937; padding-top: 0.5rem; display: flex; justify-content: space-between;">
                <span>Aporte:</span>
                <strong style="color: #ffffff;">+{{ $breakdown['gaps']['contribution'] }}%</strong>
            </div>
        </div>

    </div>
</div>