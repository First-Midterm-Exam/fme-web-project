<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>DIMA LTDA — Plataforma CMMI</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles

        <style>
            :root {
                --barra-ancho: 76px;
                --barra-ancho-abierto: 272px;
                --barra-fondo: #14171c;
                --superficie: #1b1f26;
            }

            body {
                background-color: #0f1115;
            }

            .shell {
                display: flex;
                min-height: 100vh;
            }

            .barra-lateral {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 1040;
                width: var(--barra-ancho);
                background-color: var(--barra-fondo);
                border-right: 1px solid rgba(255, 255, 255, .08);
                display: flex;
                flex-direction: column;
                overflow-x: hidden;
                overflow-y: auto;
                transition: width .18s ease;
                scrollbar-width: thin;
            }

            .barra-lateral:hover,
            .barra-lateral.abierta {
                width: var(--barra-ancho-abierto);
                box-shadow: 0 0 40px rgba(0, 0, 0, .55);
            }

            .barra-lateral .rotulo,
            .barra-lateral .submenu,
            .barra-lateral .flecha {
                display: none;
            }

            .barra-lateral:hover .rotulo,
            .barra-lateral.abierta .rotulo,
            .barra-lateral:hover .submenu,
            .barra-lateral.abierta .submenu,
            .barra-lateral:hover .flecha,
            .barra-lateral.abierta .flecha {
                display: inline;
            }

            .barra-lateral:hover .submenu.collapse:not(.show),
            .barra-lateral.abierta .submenu.collapse:not(.show) {
                display: none;
            }

            .barra-marca {
                display: flex;
                align-items: center;
                gap: .75rem;
                padding: 1rem 1.15rem;
                color: #fff;
                text-decoration: none;
                white-space: nowrap;
                border-bottom: 1px solid rgba(255, 255, 255, .08);
            }

            .barra-lateral .nav-link {
                display: flex;
                align-items: center;
                gap: .8rem;
                padding: .62rem 1.25rem;
                color: #9aa4b2;
                white-space: nowrap;
                border-left: 3px solid transparent;
            }

            .barra-lateral .nav-link:hover {
                color: #fff;
                background-color: rgba(255, 255, 255, .05);
            }

            .barra-lateral .nav-link.activo {
                color: #fff;
                background-color: rgba(13, 110, 253, .16);
                border-left-color: #0d6efd;
            }

            .barra-lateral .nav-link i:first-child {
                font-size: 1.15rem;
                min-width: 1.4rem;
                text-align: center;
            }

            .barra-lateral .submenu .nav-link {
                padding-left: 3.4rem;
                font-size: .9rem;
            }

            .barra-lateral .flecha {
                margin-left: auto;
                font-size: .75rem;
                transition: transform .18s ease;
            }

            .barra-lateral [aria-expanded="true"] .flecha {
                transform: rotate(90deg);
            }

            .barra-seccion {
                padding: 1rem 1.25rem .35rem;
                font-size: .7rem;
                letter-spacing: .09em;
                text-transform: uppercase;
                color: #5c6673;
                white-space: nowrap;
            }

            .contenido {
                flex: 1;
                min-width: 0;
                margin-left: var(--barra-ancho);
                display: flex;
                flex-direction: column;
            }

            .barra-superior {
                position: sticky;
                top: 0;
                z-index: 1030;
                background-color: var(--superficie);
                border-bottom: 1px solid rgba(255, 255, 255, .08);
            }

            .velo {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 1035;
                background-color: rgba(0, 0, 0, .6);
            }

            @media (max-width: 991.98px) {
                .barra-lateral {
                    width: 0;
                    border-right: 0;
                }

                .barra-lateral:hover {
                    width: 0;
                }

                .barra-lateral.abierta {
                    width: var(--barra-ancho-abierto);
                    border-right: 1px solid rgba(255, 255, 255, .08);
                }

                .contenido {
                    margin-left: 0;
                }

                .velo.visible {
                    display: block;
                }
            }
        </style>
    </head>
    <body class="text-white">
        <div class="shell">
            @include('partials.sidebar')

            <div class="velo" data-velo></div>

            <div class="contenido">
                @include('partials.topbar')

                <main class="flex-grow-1 py-4">
                    <div class="container-fluid px-4">
                        {{ $slot }}
                    </div>
                </main>

                <footer class="py-3 border-top border-secondary text-center text-secondary">
                    <small>&copy; {{ date('Y') }} DIMA LTDA — Plataforma CMMI. Todos los derechos reservados.</small>
                </footer>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function iniciarBarraLateral() {
                const barra = document.querySelector('[data-barra-lateral]');
                const velo = document.querySelector('[data-velo]');

                if (! barra) {
                    return;
                }

                const cerrarSubmenus = () => {
                    barra.querySelectorAll('.submenu.show').forEach((submenu) => {
                        bootstrap.Collapse.getOrCreateInstance(submenu).hide();
                    });
                };

                const cerrarBarra = () => {
                    barra.classList.remove('abierta');
                    velo?.classList.remove('visible');
                    cerrarSubmenus();
                };

                barra.addEventListener('mouseleave', cerrarBarra);
                velo?.addEventListener('click', cerrarBarra);

                document.querySelectorAll('[data-abrir-barra]').forEach((boton) => {
                    boton.addEventListener('click', () => {
                        barra.classList.add('abierta');
                        velo?.classList.add('visible');
                    });
                });
            }

            document.addEventListener('DOMContentLoaded', iniciarBarraLateral);
            document.addEventListener('livewire:navigated', iniciarBarraLateral);
        </script>
        @livewireScripts
    </body>
</html>
