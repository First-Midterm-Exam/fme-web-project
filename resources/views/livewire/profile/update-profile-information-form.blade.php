<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header class="mb-4">
        <h4 class="fw-bold text-white mb-1">
            <i class="bi bi-person-gear text-primary me-2"></i>Información del Perfil
        </h4>
        <p class="text-secondary small mb-0">
            Actualice la información de su cuenta y la dirección de correo electrónico.
        </p>
    </header>

    <form wire:submit="updateProfileInformation">
        <div class="mb-3">
            <label for="profile_name" class="form-label fw-semibold text-white">Nombre Completo</label>
            <input wire:model="name" id="profile_name" name="name" type="text" class="form-control bg-dark text-white border-secondary @error('name') is-invalid @enderror" required autofocus autocomplete="name">
            <x-input-error class="invalid-feedback d-block mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <label for="profile_email" class="form-label fw-semibold text-white">Correo Electrónico</label>
            <input wire:model="email" id="profile_email" name="email" type="email" class="form-control bg-dark text-white border-secondary @error('email') is-invalid @enderror" required autocomplete="username">
            <x-input-error class="invalid-feedback d-block mt-1" :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2 alert alert-warning d-flex align-items-center justify-content-between p-2">
                    <span class="small">Su dirección de correo electrónico no está verificada.</span>
                    <button wire:click.prevent="sendVerification" class="btn btn-sm btn-outline-dark">
                        Reenviar correo de verificación
                    </button>
                </div>
                @if (session('status') === 'verification-link-sent')
                    <div class="mt-2 text-success small">
                        Se ha enviado un nuevo enlace de verificación a su correo electrónico.
                    </div>
                @endif
            @endif
        </div>

        <div class="d-flex align-items-center gap-3 mt-4">
            <button type="submit" class="btn btn-primary fw-semibold px-4">
                <i class="bi bi-floppy me-1"></i>Guardar Cambios
            </button>

            <x-action-message class="text-success small fw-bold" on="profile-updated">
                <i class="bi bi-check-circle me-1"></i>Guardado correctamente.
            </x-action-message>
        </div>
    </form>
</section>
