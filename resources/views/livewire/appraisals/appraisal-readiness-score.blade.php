<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Appraisal Readiness Score</h1>
            <p class="text-sm text-gray-400 mt-1">
                Appraisal: <span class="text-indigo-400 font-medium">{{ $appraisal->name }}</span> | 
                Proyecto: <span class="text-gray-200 font-medium">{{ $appraisal->project->name ?? 'N/A' }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('appraisals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">
                &larr; Volver
            </a>
        </div>
    </div>

    <!-- Criterio 3 / RNF-14: Texto de Descargo Obligatorio -->
    <div class="p-4 rounded-xl bg-amber-950/30 border border-amber-600/30 text-amber-200">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="text-xs leading-relaxed">
                <span class="font-bold uppercase tracking-wider block text-amber-300 mb-0.5">Aviso Legal y de Certificación (RNF-14)</span>
                {{ $disclaimer }}
            </div>
        </div>
    </div>

    <!-- Criterios 1 y 2: Score de 0% a 100% y Desglose Ponderado -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Puntuación Global -->
        <div class="p-6 rounded-2xl bg-gray-900 border border-gray-800 flex flex-col justify-center items-center text-center">
            <span class="text-xs font-semibold uppercase text-gray-400 tracking-wider">Score Global</span>
            <div class="text-5xl font-black mt-3 {{ $score >= 80 ? 'text-emerald-400' : ($score >= 50 ? 'text-amber-400' : 'text-rose-400') }}">
                {{ $score }}%
            </div>
            <span class="text-xs text-gray-500 mt-2">
                {{ $score >= 80 ? 'Preparación Alta' : ($score >= 50 ? 'Preparación Media' : 'Preparación Baja') }}
            </span>
        </div>

        <!-- Componente: Prácticas (40%) -->
        <div class="p-5 rounded-2xl bg-gray-900 border border-gray-800 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center text-xs text-gray-400 mb-2">
                    <span class="font-medium">Prácticas Evaluadas</span>
                    <span class="text-indigo-400 font-semibold">{{ $breakdown['practices']['weight'] }}% Peso</span>
                </div>
                <div class="text-2xl font-bold text-white">
                    {{ $breakdown['practices']['count'] }} / {{ $breakdown['practices']['total'] }}
                </div>
                <div class="w-full bg-gray-800 h-2 rounded-full mt-3 overflow-hidden">
                    <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" style="width: {{ $breakdown['practices']['completion_rate'] }}%"></div>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-4 flex justify-between border-t border-gray-800/80 pt-2">
                <span>Aporte:</span>
                <span class="text-white font-semibold">+{{ $breakdown['practices']['contribution'] }}%</span>
            </div>
        </div>

        <!-- Componente: Evidencias (40%) -->
        <div class="p-5 rounded-2xl bg-gray-900 border border-gray-800 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center text-xs text-gray-400 mb-2">
                    <span class="font-medium">Evidencias Verificadas</span>
                    <span class="text-cyan-400 font-semibold">{{ $breakdown['evidences']['weight'] }}% Peso</span>
                </div>
                <div class="text-2xl font-bold text-white">
                    {{ $breakdown['evidences']['count'] }} / {{ $breakdown['evidences']['required'] }}
                </div>
                <div class="w-full bg-gray-800 h-2 rounded-full mt-3 overflow-hidden">
                    <div class="bg-cyan-500 h-full rounded-full transition-all duration-500" style="width: {{ $breakdown['evidences']['completion_rate'] }}%"></div>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-4 flex justify-between border-t border-gray-800/80 pt-2">
                <span>Aporte:</span>
                <span class="text-white font-semibold">+{{ $breakdown['evidences']['contribution'] }}%</span>
            </div>
        </div>

        <!-- Componente: Gaps (20%) -->
        <div class="p-5 rounded-2xl bg-gray-900 border border-gray-800 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center text-xs text-gray-400 mb-2">
                    <span class="font-medium">Salud de Gaps</span>
                    <span class="text-emerald-400 font-semibold">{{ $breakdown['gaps']['weight'] }}% Peso</span>
                </div>
                <div class="text-2xl font-bold {{ $breakdown['gaps']['open_count'] > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                    {{ $breakdown['gaps']['open_count'] }} abiertos
                </div>
                <div class="w-full bg-gray-800 h-2 rounded-full mt-3 overflow-hidden">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $breakdown['gaps']['health_rate'] }}%"></div>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-4 flex justify-between border-t border-gray-800/80 pt-2">
                <span>Aporte:</span>
                <span class="text-white font-semibold">+{{ $breakdown['gaps']['contribution'] }}%</span>
            </div>
        </div>
    </div>
</div>
