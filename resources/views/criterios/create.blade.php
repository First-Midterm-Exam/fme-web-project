<x-app-layout>
    <x-slot name="header">Nuevo Criterio</x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="{{ route('appraisals.practices', $appraisal->id) }}" class="text-decoration-none text-info">{{ $appraisal->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('criterios.index', [$appraisal, $practice]) }}" class="text-decoration-none text-info">{{ $practice->code }}</a></li>
                    <li class="breadcrumb-item active text-secondary" aria-current="page">Nuevo criterio</li>
                </ol>
            </nav>

            <div class="card bg-dark text-white border-secondary shadow-sm">
                <div class="card-header bg-dark border-secondary py-3">
                    <h2 class="h6 mb-0 fw-bold">
                        <i class="bi bi-plus-circle me-2 text-info"></i>Registrar Criterio de Evaluación
                    </h2>
                    <div class="text-secondary small mt-1">{{ $practice->code }} — {{ $practice->name }}</div>
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

                    <form action="{{ route('criterios.store', [$appraisal, $practice]) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="code" class="form-label fw-semibold">Código del Criterio <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" maxlength="50" class="form-control bg-dark text-white border-secondary @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="Ej: {{ $practice->code }}-C3" required>
                            @error('code')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label fw-semibold">Descripción del Criterio <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion" rows="4" class="form-control bg-dark text-white border-secondary @error('descripcion') is-invalid @enderror" placeholder="Ingrese las condiciones objetivas y específicas...">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="orden" class="form-label fw-semibold">Orden de Visualización</label>
                            <input type="number" name="orden" id="orden" class="form-control bg-dark text-white border-secondary" value="{{ old('orden', 0) }}" min="0">
                            <div class="form-text text-secondary">Define la posición en que se mostrará este criterio dentro del checklist.</div>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" name="required" id="required" class="form-check-input" value="1" {{ old('required', true) ? 'checked' : '' }}>
                            <label for="required" class="form-check-label">Criterio Obligatorio</label>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" name="estado" id="estado" class="form-check-input" value="1" {{ old('estado', true) ? 'checked' : '' }}>
                            <label for="estado" class="form-check-label">Criterio Activo</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('criterios.index', [$appraisal, $practice]) }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary fw-semibold">
                                <i class="bi bi-save me-1"></i>Guardar Criterio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
