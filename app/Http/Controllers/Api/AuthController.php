<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = User::where('email', $request->string('email')->toString())->first();

        if (! $usuario instanceof User
            || ! Hash::check($request->string('password')->toString(), $usuario->password)
            || ! $usuario->is_active) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $token = $usuario->createToken($request->input('device_name') ?: 'app-movil')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UsuarioResource($usuario),
        ]);
    }

    public function logout(Request $request): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }

    public function me(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user());
    }
}
