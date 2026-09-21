<x-app-layout>
    <x-slot name="header">Mi Perfil</x-slot>

    <div class="row g-4 justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
                <div class="card-body p-4">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="card bg-dark text-white border-secondary shadow-sm mb-4">
                <div class="card-body p-4">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="card bg-dark text-white border-danger shadow-sm mb-4">
                <div class="card-body p-4">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
