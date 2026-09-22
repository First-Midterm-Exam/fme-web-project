<div class="p-6 max-w-7xl mx-auto space-y-6 text-gray-100">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">¿Qué me falta? - Pendientes Priorizados</h1>
            <p class="text-sm text-gray-400 mt-1">
                Appraisal Activo: <span class="text-indigo-400 font-medium">{{ $appraisal->name }}</span> | 
                Proyecto: <span class="text-gray-200 font-medium">{{ $appraisal->project->name ?? 'N/A' }}</span>
            </p>
        </div>
        <div>
            <a href="{{ route('appraisals.index') }}" class="px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors inline-block">
                &larr; Volver a Appraisals
            </a>
        </div>
    </div>

    <!-- Filtros rápidos de categoría -->
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button wire:click="$set('filterType', 'all')" 
                style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; 
                       background-color: {{ $filterType === 'all' ? '#4f46e5' : '#1f2937' }}; color: #ffffff;">
            Todos ({{ $totalCount }})
        </button>
        <button wire:click="$set('filterType', 'gap')" 
                style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; 
                       background-color: {{ $filterType === 'gap' ? '#dc2626' : '#1f2937' }}; color: #ffffff;">
            Gaps ({{ $gapsCount }})
        </button>
        <button wire:click="$set('filterType', 'action')" 
                style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; 
                       background-color: {{ $filterType === 'action' ? '#d97706' : '#1f2937' }}; color: #ffffff;">
            Acciones ({{ $actionsCount }})
        </button>
        <button wire:click="$set('filterType', 'practice')" 
                style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; 
                       background-color: {{ $filterType === 'practice' ? '#2563eb' : '#1f2937' }}; color: #ffffff;">
            Prácticas ({{ $practicesCount }})
        </button>
        <button wire:click="$set('filterType', 'evidence')" 
                style="padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; 
                       background-color: {{ $filterType === 'evidence' ? '#0d9488' : '#1f2937' }}; color: #ffffff;">
            Evidencias ({{ $evidencesCount }})
        </button>
    </div>

    <!-- Listado Consolidado -->
    <div style="background-color: #111827; border: 1px solid #1f2937; border-radius: 0.75rem; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
                <tr style="background-color: #1f2937; color: #9ca3af; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">
                    <th style="padding: 0.75rem 1rem;">Prioridad / Tipo</th>
                    <th style="padding: 0.75rem 1rem;">Descripción del Pendiente</th>
                    <th style="padding: 0.75rem 1rem;">Severidad / Estado</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Acción Directa</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $activity)
                    <tr style="border-top: 1px solid #1f2937; background-color: {{ $activity['is_overdue'] ? 'rgba(153, 27, 27, 0.15)' : 'transparent' }};">
                        <td style="padding: 1rem;">
                            <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;
                                background-color: {{ 
                                    $activity['category'] === 'gap' ? 'rgba(239, 68, 68, 0.2)' : 
                                    ($activity['category'] === 'action' ? 'rgba(245, 158, 11, 0.2)' : 
                                    ($activity['category'] === 'practice' ? 'rgba(59, 130, 246, 0.2)' : 'rgba(20, 184, 166, 0.2)')) 
                                }};
                                color: {{ 
                                    $activity['category'] === 'gap' ? '#f87171' : 
                                    ($activity['category'] === 'action' ? '#fbbf24' : 
                                    ($activity['category'] === 'practice' ? '#60a5fa' : '#2dd4bf')) 
                                }};">
                                {{ $activity['type'] }}
                            </span>
                        </td>
                        <td style="padding: 1rem; color: #f3f4f6;">
                            <div style="font-weight: 600;">{{ $activity['title'] }}</div>
                            @if($activity['due_date'])
                                <div style="font-size: 0.75rem; color: {{ $activity['is_overdue'] ? '#f87171' : '#9ca3af' }}; margin-top: 0.25rem;">
                                    Fecha límite: {{ \Carbon\Carbon::parse($activity['due_date'])->format('d/m/Y') }}
                                    @if($activity['is_overdue'])
                                        <strong>(¡VENCIDO!)</strong>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="padding: 1rem;">
                            <span style="font-size: 0.8rem; font-weight: 500; color: {{ $activity['priority_score'] >= 100 ? '#f87171' : ($activity['priority_score'] >= 60 ? '#fbbf24' : '#9ca3af') }};">
                                {{ $activity['severity'] }}
                            </span>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <a href="{{ $activity['action_url'] }}" 
                               style="display: inline-block; padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 0.375rem; background-color: #374151; color: #ffffff; text-decoration: none;">
                                {{ $activity['action_label'] }} &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 2.5rem; text-align: center; color: #9ca3af;">
                            ¡Excelente trabajo! No hay actividades pendientes registradas para este appraisal.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>