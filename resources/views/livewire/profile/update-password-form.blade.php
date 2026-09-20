<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header class="mb-4">
        <h4 class="fw-bold text-white mb-1">
            <i class="bi bi-key text-primary me-2"></i>Actualizar Contraseña
        </h4>
        <p class="text-secondary small mb-0">
            Asegúrese de que su cuenta utilice una contraseña larga y aleatoria para mantener la seguridad.
        </p>
    </header>

    <form wire:submit="updatePassword">
        <div class="mb-3">
            <label for="update_password_current_password" class="form-label fw-semibold text-white">Contraseña Actual</label>
            <input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="form-control bg-dark text-white border-secondary @error('current_password') is-invalid @enderror" autocomplete="current-password">
            <x-input-error :messages="$errors->get('current_password')" class="invalid-feedback d-block mt-1" />
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label fw-semibold text-white">Nueva Contraseña</label>
            <input wire:model="password" id="update_password_password" name="password" type="password" class="form-control bg-dark text-white border-secondary @error('password') is-invalid @enderror" autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="invalid-feedback d-block mt-1" />
        </div>

        <div class="mb-3">
            <label for="update_password_password_confirmation" class="form-label fw-semibold text-white">Confirmar Nueva Contraseña</label>
            <input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control bg-dark text-white border-secondary @error('password_confirmation') is-invalid @enderror" autocomplete="new-password">
            <x-input-error :messages="$errors->get('password_confirmation')" class="invalid-feedback d-block mt-1" />
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4">
                <i class="bi bi-shield-check me-1"></i>Actualizar Contraseña
            </button>

            <x-action-message class="text-success small fw-bold" on="password-updated">
                <i class="bi bi-check-circle me-1"></i>Contraseña actualizada.
            </x-action-message>
        </div>
    </form>
</section>
