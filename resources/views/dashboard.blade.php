<x-app-layout>
    <x-slot name="header">Panel Principal</x-slot>

    <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 text-primary rounded-circle p-3 d-inline-flex">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <div>
                    <h2 class="h5 fw-bold mb-1">Bienvenido, {{ auth()->user()->name }}</h2>
                    <p class="text-secondary mb-0 small">
                        Plataforma de preparación para el appraisal CMMI V3.0 de DIMA LTDA.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @can('ver-readiness')
        <livewire:dashboard.statistics />
    @endcan

    <div class="row g-4">
        @foreach (App\Support\Modulos::items() as $modulo)
            @can($modulo['capacidad'])
                <div class="col-md-6 col-xl-4">
                    <a href="{{ route($modulo['ruta']) }}" class="card h-100 bg-dark text-white border-secondary shadow-sm text-decoration-none" wire:navigate>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi {{ $modulo['icono'] }} text-primary fs-5"></i>
                                <h3 class="h6 fw-bold mb-0">{{ $modulo['etiqueta'] }}</h3>
                            </div>
                            <p class="text-secondary small mb-0">{{ $modulo['descripcion'] }}</p>
                        </div>
                    </a>
                </div>
            @endcan
        @endforeach
    </div>
</x-app-layout>
