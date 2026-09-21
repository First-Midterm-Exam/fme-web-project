<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario instanceof User && $usuario->is_active === false) {
            $token = $usuario->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            throw new AuthenticationException;
        }

        return $next($request);
    }
}
