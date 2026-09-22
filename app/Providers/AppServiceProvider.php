<?php

namespace App\Providers;

use App\Models\Rol;
use App\Models\User;
use App\Services\Asistente\Fuentes\FuenteGaps;
use App\Services\Asistente\Fuentes\GapsRegistrados;
use App\Services\Documentos\RevisorFormato;
use App\Services\Documentos\RevisorFormatoDePrueba;
use App\Services\Ia\ClienteGroq;
use App\Support\Capacidades;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClienteGroq::class, fn (): ClienteGroq => new ClienteGroq(
            (string) config('services.groq.key'),
            (string) config('services.groq.url'),
            (string) config('services.groq.model'),
            (int) config('services.groq.timeout'),
        ));

        $this->app->bind(RevisorFormato::class, RevisorFormatoDePrueba::class);
        $this->app->bind(FuenteGaps::class, GapsRegistrados::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registrarCapacidades();
        $this->registrarDirectivaDeRol();
        $this->registrarLimitesDeApi();
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

    private function registrarLimitesDeApi(): void
    {
        RateLimiter::for('asistente', function (Request $request): Limit {
            $usuario = $request->user();

            return Limit::perMinute(10)->by($usuario instanceof User ? 'usuario:'.$usuario->id : 'ip:'.$request->ip());
        });
    }
}
