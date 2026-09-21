<header class="barra-superior">
    <div class="d-flex align-items-center gap-3 px-4 py-3">
        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-abrir-barra aria-label="Abrir menú">
            <i class="bi bi-list"></i>
        </button>

        <h1 class="h5 fw-semibold text-white mb-0 text-truncate">
            {{ $header ?? 'Panel Principal' }}
        </h1>

        <livewire:layout.navigation />
    </div>
</header>
