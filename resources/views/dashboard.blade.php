<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold text-dark mb-0">
            Panel Principal — DIMA LTDA
        </h2>
    </x-slot>

    <div class="container py-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-primary text-white rounded-circle p-3 d-inline-flex">
                        <i class="bi bi-shield-check fs-2"></i>
                    </div>
                    <div>
                        <h4 class="card-title fw-bold mb-1">¡Bienvenido a la Plataforma CMMI, {{ auth()->user()->name }}!</h4>
                        <p class="card-text text-muted mb-0">Has iniciado sesión correctamente con el rol: <span class="badge bg-primary fs-6 fw-normal">{{ auth()->user()->roles->first()?->name ?? 'Sin Rol' }}</span></p>
                    </div>
                </div>
                
                <hr class="my-4">

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border bg-light shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-primary"><i class="bi bi-person-badge me-2"></i>Tu Cuenta</h5>
                                <p class="card-text text-secondary small">
                                    <strong>Nombre:</strong> {{ auth()->user()->name }}<br>
                                    <strong>Correo:</strong> {{ auth()->user()->email }}<br>
                                    <strong>Estado:</strong> <span class="badge bg-success">Activo</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    @can('viewAny', App\Models\User::class)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border bg-light shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold text-success"><i class="bi bi-people-fill me-2"></i>Gestión de Usuarios</h5>
                                    <p class="card-text text-secondary small">Acceso exclusivo para Administradores para dar de alta, editar y dar de baja usuarios.</p>
                                    <a href="{{ route('users.index') }}" class="btn btn-success btn-sm">
                                        Ir a Gestión de Usuarios <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
