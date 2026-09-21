<x-app-layout>
    <x-slot name="header">Acceso denegado</x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card bg-dark text-white border-danger shadow-lg">
                <div class="card-body p-5 text-center">
                    <div class="bg-danger bg-opacity-25 text-danger rounded-circle d-inline-flex p-4 mb-3">
                        <i class="bi bi-shield-lock-fill fs-1"></i>
                    </div>

                    <h2 class="display-6 fw-bold mb-2">403</h2>
                    <h3 class="h5 fw-semibold mb-3">Acceso denegado</h3>

                    <p class="text-secondary mb-4">
                        No cuentas con los permisos necesarios para acceder a esta sección.
                        Si crees que se trata de un error, comunícate con el administrador de la plataforma.
                    </p>

                    @auth
                        <p class="text-secondary small mb-4">
                            Sesión iniciada como <strong class="text-white">{{ auth()->user()->name }}</strong>
                            con el rol
                            <span class="badge {{ auth()->user()->colorDeRol() }} fw-normal">{{ auth()->user()->etiquetaDeRol() }}</span>.
                        </p>
                    @endauth

                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-primary fw-semibold" wire:navigate>
                        <i class="bi bi-house-door me-1"></i>Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
