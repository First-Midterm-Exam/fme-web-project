<x-app-layout>
    <x-slot name="header">{{ $modulo['etiqueta'] }}</x-slot>

    <div class="card bg-dark text-white border-secondary shadow-sm">
        <div class="card-body p-5 text-center">
            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-inline-flex p-4 mb-3">
                <i class="bi {{ $modulo['icono'] }} fs-1"></i>
            </div>

            <h3 class="h4 fw-bold mb-2">{{ $modulo['etiqueta'] }}</h3>
            <p class="text-secondary mb-4">{{ $modulo['descripcion'] }}</p>

            <p class="text-secondary small mb-0">
                <i class="bi bi-inbox me-1"></i>Aún no hay información registrada en esta sección.
            </p>
        </div>
    </div>
</x-app-layout>
