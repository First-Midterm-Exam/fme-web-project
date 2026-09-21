<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HU-09: Administrar Criterios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Criterios de Evaluación</h2>
        <a href="{{ route('criterios.create', ['practica_id' => $practicaId ?? 1]) }}" class="btn btn-primary">
            + Nuevo Criterio
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('criterios.index') }}" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="practica_id" class="col-form-label fw-bold">Filtrar por Práctica (ID):</label>
                </div>
                <div class="col-auto">
                    <input type="number" name="practica_id" id="practica_id" class="form-control" value="{{ $practicaId }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">Filtrar</button>
                    <a href="{{ route('criterios.index') }}" class="btn btn-outline-secondary">Ver Todos</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Orden</th>
                        <th>Práctica ID</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($criterios as $criterio)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $criterio->orden }}</span></td>
                            <td>{{ $criterio->practica_id }}</td>
                            <td>{{ $criterio->descripcion }}</td>
                            <td>
                                @if($criterio->estado)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('criterios.edit', $criterio) }}" class="btn btn-sm btn-warning">Editar</a>
                                <form action="{{ route('criterios.destroy', $criterio) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este criterio?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No se encontraron criterios registrados para esta práctica.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>