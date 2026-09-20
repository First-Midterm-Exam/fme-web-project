<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}" wire:navigate>
            <i class="bi bi-shield-check text-primary fs-3"></i>
            <span>DIMA LTDA — CMMI</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-bold' : '' }}" href="{{ route('dashboard') }}" wire:navigate>
                        <i class="bi bi-speedometer2 me-1"></i>Panel Principal
                    </a>
                </li>
                @can('viewAny', App\Models\User::class)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.index') ? 'active fw-bold' : '' }}" href="{{ route('users.index') }}" wire:navigate>
                            <i class="bi bi-people-fill me-1"></i>Gestión de Usuarios
                        </a>
                    </li>
                @endcan
            </ul>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white fw-normal px-2 py-1">
                    {{ auth()->user()->roles->first()?->name ?? 'Sin Rol' }}
                </span>

                <a href="{{ route('profile') }}" class="btn btn-outline-light btn-sm d-flex align-items-center gap-1" wire:navigate title="Perfil">
                    <i class="bi bi-person-circle me-1"></i><span>{{ auth()->user()->name }}</span>
                </a>

                <button wire:click="logout" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 ms-1" title="Cerrar Sesión">
                    <i class="bi bi-box-arrow-right me-1"></i><span>Cerrar Sesión</span>
                </button>
            </div>
        </div>
    </div>
</nav>
