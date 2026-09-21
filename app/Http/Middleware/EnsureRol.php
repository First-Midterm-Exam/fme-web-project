<?php

namespace App\Http\Middleware;

use App\Models\Rol;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRol
{
    private const CLAVE_CACHE = 'roles.mapa_slugs';

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof User || ! $usuario->tieneRol(...$this->resolver($roles))) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $roles
     * @return array<int, int>
     */
    private function resolver(array $roles): array
    {
        $mapa = $this->mapaDeSlugs();

        return array_values(array_filter(array_map(
            fn (string $slug): ?int => $mapa[$slug] ?? null,
            $roles
        )));
    }

    /**
     * @return array<string, int>
     */
    private function mapaDeSlugs(): array
    {
        $contenedor = app();

        if (! $contenedor->bound(self::CLAVE_CACHE)) {
            $contenedor->instance(self::CLAVE_CACHE, Rol::mapaDeSlugs());
        }

        /** @var array<string, int> $mapa */
        $mapa = $contenedor->make(self::CLAVE_CACHE);

        return $mapa;
    }
}
