<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 font-weight-bold text-white mb-0">
            Perfil de Usuario
        </h2>
    </x-slot>

    <div class="container py-4">
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
    </div>
</x-app-layout>
