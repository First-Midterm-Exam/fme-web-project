<aside class="barra-lateral" data-barra-lateral>
    <a href="{{ route('dashboard') }}" class="barra-marca" wire:navigate>
        <i class="bi bi-shield-check text-primary fs-4"></i>
        <span class="rotulo fw-bold">DIMA LTDA</span>
    </a>

    <ul class="nav flex-column mt-2 mb-3">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'activo' : '' }}" href="{{ route('dashboard') }}" wire:navigate>
                <i class="bi bi-speedometer2"></i>
                <span class="rotulo">Panel Principal</span>
            </a>
        </li>

        @foreach (App\Support\Modulos::grupos() as $indice => $grupo)
            @php
                $visibles = array_values(array_filter($grupo['items'], fn ($item) => Illuminate\Support\Facades\Gate::allows($item['capacidad'])));
                $activo = collect($visibles)->contains(fn ($item) => request()->routeIs($item['ruta']));
            @endphp

            @continue(count($visibles) === 0)

            @if (count($visibles) === 1)
                <li class="nav-item">
                    <a class="nav-link {{ $activo ? 'activo' : '' }}" href="{{ route($visibles[0]['ruta']) }}" wire:navigate>
                        <i class="bi {{ $grupo['icono'] }}"></i>
                        <span class="rotulo">{{ $visibles[0]['etiqueta'] }}</span>
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link {{ $activo ? 'activo' : '' }}" href="#grupo-{{ $indice }}" data-bs-toggle="collapse" role="button" aria-expanded="{{ $activo ? 'true' : 'false' }}" aria-controls="grupo-{{ $indice }}">
                        <i class="bi {{ $grupo['icono'] }}"></i>
                        <span class="rotulo">{{ $grupo['etiqueta'] }}</span>
                        <i class="bi bi-chevron-right flecha"></i>
                    </a>

                    <ul class="nav flex-column submenu collapse {{ $activo ? 'show' : '' }}" id="grupo-{{ $indice }}">
                        @foreach ($visibles as $item)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs($item['ruta']) ? 'activo' : '' }}" href="{{ route($item['ruta']) }}" wire:navigate>
                                    <i class="bi {{ $item['icono'] }}"></i>
                                    <span class="rotulo">{{ $item['etiqueta'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endforeach
    </ul>

    <div class="mt-auto">
        @auth
        <div class="barra-seccion rotulo">Cuenta</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('profile') ? 'activo' : '' }}" href="{{ route('profile') }}" wire:navigate>
                    <i class="bi bi-person-circle"></i>
                    <span class="rotulo">Mi Perfil</span>
                </a>
            </li>
        </ul>
        @endauth
    </div>
</aside>
