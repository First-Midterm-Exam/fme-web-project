<x-app-layout>
    <x-slot name="header">Criterios de la Práctica</x-slot>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('proyectos.show', $appraisal->project_id) }}" class="text-decoration-none text-info">{{ $appraisal->project->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('appraisals.practices', $appraisal->id) }}" class="text-decoration-none text-info">{{ $appraisal->name }}</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">{{ $practice->code }}</li>
                    <li class="breadcrumb-item active text-secondary" aria-current="page">Criterios</li>
                </ol>
            </nav>
            <h1 class="h3 text-white fw-bold mb-1">
                <i class="bi bi-rulers me-2 text-info"></i>Criterios de Evaluación
            </h1>
            <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                <span><strong class="text-white">{{ $practice->code }}</strong> — {{ $practice->name }}</span>
                <span>•</span>
                <span><strong class="text-white">Nivel:</strong> {{ $practice->level }}</span>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('appraisals.practices.checklist', [$appraisal->id, $practice->id]) }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-ui-checks me-1"></i>Ir al Checklist
            </a>
            <a href="{{ route('criterios.create', [$appraisal, $practice]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo Criterio
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold">
                <i class="bi bi-list-check me-2 text-info"></i>Criterios definidos
                <span class="badge bg-secondary bg-opacity-50 text-light ms-2">{{ $criterios->count() }}</span>
            </h2>
            <a href="{{ route('appraisals.practices', $appraisal->id) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Prácticas
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small border-secondary">
                        <th style="width: 8%;">Orden</th>
                        <th style="width: 16%;">Código</th>
                        <th style="width: 40%;">Descripción</th>
                        <th class="text-center" style="width: 12%;">Obligatorio</th>
                        <th class="text-center" style="width: 10%;">Estado</th>
                        <th class="text-end" style="width: 14%;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($criterios as $criterio)
                        <tr class="border-secondary">
                            <td><span class="badge bg-secondary bg-opacity-50">{{ $criterio->orden }}</span></td>
                            <td><span class="font-monospace text-info">{{ $criterio->code }}</span></td>
                            <td>{{ $criterio->description }}</td>
                            <td class="text-center">
                                <span class="badge {{ $criterio->required ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $criterio->required ? 'Obligatorio' : 'Opcional' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $criterio->estado ? 'bg-success' : 'bg-danger' }}">
                                    {{ $criterio->estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('criterios.edit', [$appraisal, $practice, $criterio]) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('criterios.destroy', [$appraisal, $practice, $criterio]) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este criterio?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Esta práctica aún no tiene criterios de evaluación definidos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
