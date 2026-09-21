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

<div class="ms-auto d-flex align-items-center gap-3">
    @auth
        <span class="badge {{ auth()->user()->colorDeRol() }} fw-normal px-2 py-1 d-none d-sm-inline">
            {{ auth()->user()->etiquetaDeRol() }}
        </span>

        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2">
                    <div class="fw-semibold text-white">{{ auth()->user()->name }}</div>
                    <div class="small text-secondary">{{ auth()->user()->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile') }}" wire:navigate>
                        <i class="bi bi-person-gear me-2"></i>Mi Perfil
                    </a>
                </li>
                <li>
                    <button wire:click="logout" class="dropdown-item text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                    </button>
                </li>
            </ul>
        </div>
    @endauth

    @guest
        <a href="{{ route('login') }}" class="btn btn-primary btn-sm" wire:navigate>
            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
        </a>
    @endguest
</div>
