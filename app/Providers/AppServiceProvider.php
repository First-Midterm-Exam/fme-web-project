<?php

namespace App\Providers;

use App\Models\Rol;
use App\Models\User;
use App\Support\Capacidades;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registrarCapacidades();
        $this->registrarDirectivaDeRol();
    }

    private function registrarCapacidades(): void
    {
        foreach (Capacidades::MAPA as $capacidad => $rolIds) {
            Gate::define($capacidad, fn (User $user): bool => $user->tieneRol(...$rolIds));
        }
    }

    private function registrarDirectivaDeRol(): void
    {
        Blade::if('rol', function (string ...$slugs): bool {
            $usuario = auth()->user();

            if (! $usuario instanceof User) {
                return false;
            }

            $mapa = Rol::mapaDeSlugs();
            $rolIds = array_values(array_filter(array_map(
                fn (string $slug): ?int => $mapa[$slug] ?? null,
                $slugs
            )));

            return $rolIds !== [] && $usuario->tieneRol(...$rolIds);
        });
    }
}
