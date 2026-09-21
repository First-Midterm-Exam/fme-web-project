<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Criterio - HU-09</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-4">
<div class="container" style="max-width: 650px;">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">Modificar Criterio #{{ $criterio->id }}</h4>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('criterios.update', $criterio) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="practica_id" class="form-label">ID de la Práctica <span class="text-danger">*</span></label>
                    <input type="number" name="practica_id" id="practica_id" class="form-control" value="{{ old('practica_id', $criterio->practica_id) }}" required>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción del Criterio <span class="text-danger">*</span></label>
                    <textarea name="descripcion" id="descripcion" rows="4" class="form-control @error('descripcion') is-invalid @enderror" required>{{ old('descripcion', $criterio->descripcion) }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="orden" class="form-label">Orden de Visualización</label>
                    <input type="number" name="orden" id="orden" class="form-control" value="{{ old('orden', $criterio->orden) }}" min="0">
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" name="estado" id="estado" class="form-check-input" value="1" {{ old('estado', $criterio->estado) ? 'checked' : '' }}>
                    <label for="estado" class="form-check-label">Criterio Activo</label>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('criterios.index', ['practica_id' => $criterio->practica_id]) }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-warning">Actualizar Criterio</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>