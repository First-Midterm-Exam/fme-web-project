<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>DIMA LTDA — Plataforma CMMI</title>
        <!-- Bootstrap 5 CSS & Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="bg-light text-dark">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="#">
                    <i class="bi bi-shield-check text-primary me-2"></i>DIMA LTDA — Plataforma CMMI
                </a>
                <div class="ms-auto">
                    @if (Route::has('login'))
                        <livewire:welcome.navigation />
                    @endif
                </div>
            </div>
        </nav>

        <main class="container py-5">
            <div class="row align-items-center g-5 py-4">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-bold lh-1 mb-3 text-primary">Plataforma de Gestión de Preparación para Appraisal CMMI</h1>
                    <p class="lead text-secondary mb-4">
                        Bienvenido al sistema oficial de gestión de evidencias y preparación de procesos CMMI de DIMA LTDA. Administre usuarios, roles y requerimientos del modelo CMMI de forma centralizada y segura.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg px-4 me-md-2">
                                <i class="bi bi-speedometer2 me-2"></i>Ir al Panel Principal
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4 me-md-2">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar Sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-outline-secondary btn-lg px-4">
                                    <i class="bi bi-person-plus me-2"></i>Registrarse
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4 bg-white">
                        <div class="card-body text-center">
                            <i class="bi bi-award text-primary display-1 mb-3"></i>
                            <h3 class="card-title fw-bold">Calidad & Mejora Continua</h3>
                            <p class="card-text text-muted fs-6">
                                Gestión estructurada por roles: Administrador, Process Manager / CMMI Manager, Project Manager y Contributor.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="mt-auto py-4 bg-white border-top text-center text-muted">
            <div class="container">
                <small>&copy; {{ date('Y') }} DIMA LTDA — Todos los derechos reservados.</small>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
