<div class="d-flex align-items-center gap-2">
    @auth
        <a href="{{ url('/dashboard') }}" class="btn btn-outline-light btn-sm fw-semibold">
            <i class="bi bi-speedometer2 me-1"></i>Panel Principal
        </a>
    @else
        <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}" class="btn btn-primary btn-sm fw-semibold ms-1">
                <i class="bi bi-person-plus me-1"></i>Registrarse
            </a>
        @endif
    @endauth
</div>
