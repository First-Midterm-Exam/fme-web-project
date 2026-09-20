<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section>
    <header class="mb-4">
        <h4 class="fw-bold text-danger mb-1">
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>Eliminar Cuenta
        </h4>
        <p class="text-secondary small mb-0">
            Una vez eliminada la cuenta, todos sus datos e historial serán borrados permanentemente.
        </p>
    </header>

    <button type="button" class="btn btn-outline-danger fw-semibold" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        <i class="bi bi-trash me-1"></i>Eliminar Cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-4 bg-dark text-white rounded">
            <h5 class="fw-bold text-white mb-2">
                ¿Está seguro de que desea eliminar su cuenta?
            </h5>

            <p class="text-secondary small mb-3">
                Una vez eliminada su cuenta, no se podrán recuperar sus datos. Ingrese su contraseña para confirmar.
            </p>

            <div class="mb-3">
                <label for="delete_account_password" class="form-label fw-semibold text-white">Contraseña Actual</label>
                <input wire:model="password" id="delete_account_password" name="password" type="password" class="form-control bg-dark text-white border-secondary @error('password') is-invalid @enderror" placeholder="••••••••" required>
                <x-input-error :messages="$errors->get('password')" class="invalid-feedback d-block mt-1" />
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-secondary" x-on:click="$dispatch('close')">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-danger fw-semibold">
                    Confirmar Eliminación
                </button>
            </div>
        </form>
    </x-modal>
</section>
