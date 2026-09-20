<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-4">
        <h4 class="fw-bold text-white mb-1">Iniciar Sesión</h4>
        <p class="text-secondary small mb-0">Ingrese sus credenciales para acceder a la plataforma</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-3 alert alert-info" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold text-white">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-dark text-secondary border-secondary"><i class="bi bi-envelope"></i></span>
                <input wire:model="form.email" id="email" type="email" name="email" class="form-control bg-dark text-white border-secondary @error('form.email') is-invalid @enderror" placeholder="nombre@dima.cl" required autofocus autocomplete="username">
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="invalid-feedback d-block mt-1" />
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold text-white">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-dark text-secondary border-secondary"><i class="bi bi-lock"></i></span>
                <input wire:model="form.password" id="password" type="password" name="password" class="form-control bg-dark text-white border-secondary @error('form.password') is-invalid @enderror" placeholder="••••••••" required autocomplete="current-password">
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="invalid-feedback d-block mt-1" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input wire:model="form.remember" id="remember" type="checkbox" class="form-check-input bg-dark border-secondary" name="remember">
                <label for="remember" class="form-check-label text-secondary small">Recordarme</label>
            </div>
            @if (Route::has('password.request'))
                <a class="text-decoration-none small text-primary" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
            </button>
        </div>

        @if (Route::has('register'))
            <div class="text-center pt-3 border-top border-secondary">
                <span class="text-secondary small">¿No tienes una cuenta?</span>
                <a href="{{ route('register') }}" class="text-decoration-none small fw-bold text-primary ms-1" wire:navigate>
                    Registrarse
                </a>
            </div>
        @endif
    </form>
</div>
