<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>DIMA LTDA — Plataforma CMMI</title>

        <!-- Bootstrap 5 CSS & Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @livewireStyles
    </head>
    <body class="bg-dark text-white min-vh-100 d-flex flex-column">
        <livewire:layout.navigation />

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-dark border-bottom border-secondary shadow-sm">
                <div class="container py-3">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main class="py-4 flex-grow-1">
            {{ $slot }}
        </main>

        <footer class="py-3 bg-dark border-top border-secondary text-center text-muted mt-auto">
            <div class="container">
                <small>&copy; {{ date('Y') }} DIMA LTDA — Plataforma CMMI. Todos los derechos reservados.</small>
            </div>
        </footer>

        <!-- Bootstrap 5 Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @livewireScripts
    </body>
</html>
