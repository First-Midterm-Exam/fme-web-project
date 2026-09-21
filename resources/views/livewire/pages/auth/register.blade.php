<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->assignRole(\App\Models\Rol::findById(\App\Models\Rol::COLABORADOR));

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-4">
        <h4 class="fw-bold text-white mb-1">Crear una Cuenta</h4>
        <p class="text-secondary small mb-0">Complete sus datos para registrarse en la plataforma</p>
    </div>

    <form wire:submit="register">
        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold text-white">Nombre Completo</label>
            <div class="input-group">
                <span class="input-group-text bg-dark text-secondary border-secondary"><i class="bi bi-person"></i></span>
                <input wire:model="name" id="name" type="text" name="name" class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror" placeholder="Juan Pérez" required autofocus autocomplete="name">
            </div>
            <x-input-error :messages="$errors->get('name')" class="invalid-feedback d-block mt-1" />
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label fw-semibold text-white">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-dark text-secondary border-secondary"><i class="bi bi-envelope"></i></span>
                <input wire:model="email" id="email" type="email" name="email" class="form-control bg-dark text-white border-secondary @error('email') is-invalid @enderror" placeholder="nombre@dima.cl" required autocomplete="username">
            </div>
            <x-input-error :messages="$errors->get('email')" class="invalid-feedback d-block mt-1" />
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label fw-semibold text-white">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-dark text-secondary border-secondary"><i class="bi bi-lock"></i></span>
                <input wire:model="password" id="password" type="password" name="password" class="form-control bg-dark text-white border-secondary @error('password') is-invalid @enderror" placeholder="••••••••" required autocomplete="new-password">
            </div>
            <x-input-error :messages="$errors->get('password')" class="invalid-feedback d-block mt-1" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <label for="password_confirmation" class="form-label fw-semibold text-white">Confirmar Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-dark text-secondary border-secondary"><i class="bi bi-shield-lock"></i></span>
                <input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" class="form-control bg-dark text-white border-secondary @error('password_confirmation') is-invalid @enderror" placeholder="••••••••" required autocomplete="new-password">
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="invalid-feedback d-block mt-1" />
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                <i class="bi bi-person-check me-2"></i>Registrarse
            </button>
        </div>

        <div class="text-center pt-3 border-top border-secondary">
            <span class="text-secondary small">¿Ya tienes una cuenta?</span>
            <a href="{{ route('login') }}" class="text-decoration-none small fw-bold text-primary ms-1" wire:navigate>
                Iniciar Sesión
            </a>
        </div>
    </form>
</div>
