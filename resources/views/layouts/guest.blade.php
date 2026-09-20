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
    <body class="bg-dark text-white min-vh-100 d-flex flex-column justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-5">
                    <div class="text-center mb-4">
                        <a href="/" wire:navigate class="text-decoration-none text-white d-inline-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-primary fs-2"></i>
                            <span class="fs-4 fw-bold">DIMA LTDA — Plataforma CMMI</span>
                        </a>
                    </div>
                    <div class="card bg-dark text-white border-secondary shadow-lg rounded-3">
                        <div class="card-body p-4 p-sm-5">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bootstrap 5 Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @livewireScripts
    </body>
</html>
