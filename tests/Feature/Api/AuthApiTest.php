<?php

use App\Models\Rol;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

test('login returns a bearer token and the user with its role', function () {
    User::factory()->gestorProcesos()->create([
        'name' => 'María Fernández',
        'email' => 'mfernandez@empresa.com',
        'password' => 'Readiness2026',
    ]);

    $respuesta = $this->postJson('/api/login', [
        'email' => 'mfernandez@empresa.com',
        'password' => 'Readiness2026',
        'device_name' => 'asistente-movil',
    ]);

    $respuesta->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'nombre', 'email', 'rol' => ['id', 'nombre']]])
        ->assertJsonPath('user.nombre', 'María Fernández')
        ->assertJsonPath('user.rol.id', Rol::GESTOR_PROCESOS)
        ->assertJsonPath('user.rol.nombre', 'Gestor de Procesos');

    expect($respuesta->json('token'))->toBeString()->not->toBeEmpty()
        ->and($respuesta->json('user.id'))->toBeInt()
        ->and(PersonalAccessToken::where('name', 'asistente-movil')->exists())->toBeTrue();
});

test('login works without device name', function () {
    User::factory()->colaborador()->create(['email' => 'colab@empresa.com']);

    $this->postJson('/api/login', ['email' => 'colab@empresa.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('user.rol.nombre', 'Colaborador');
});

test('login rejects invalid credentials with 422', function (array $credenciales) {
    User::factory()->colaborador()->create(['email' => 'colab@empresa.com']);

    $this->postJson('/api/login', $credenciales)->assertStatus(422);
})->with([
    'contraseña incorrecta' => [['email' => 'colab@empresa.com', 'password' => 'incorrecta']],
    'correo inexistente' => [['email' => 'nadie@empresa.com', 'password' => 'password']],
    'sin contraseña' => [['email' => 'colab@empresa.com']],
]);

test('login rejects a deactivated user', function () {
    User::factory()->colaborador()->create(['email' => 'baja@empresa.com', 'is_active' => false]);

    $this->postJson('/api/login', ['email' => 'baja@empresa.com', 'password' => 'password'])
        ->assertStatus(422);
});

test('me returns the unwrapped user when the bearer token is valid', function () {
    $usuario = User::factory()->jefeProyecto()->create();
    $token = $usuario->createToken('asistente-movil')->plainTextToken;

    $this->withToken($token)->getJson('/api/me')
        ->assertOk()
        ->assertJsonMissingPath('data')
        ->assertJsonPath('id', $usuario->id)
        ->assertJsonPath('email', $usuario->email)
        ->assertJsonPath('rol.nombre', 'Jefe de Proyecto');
});

test('protected endpoints answer 401 without a token', function (string $metodo, string $ruta) {
    $this->json($metodo, $ruta)->assertUnauthorized();
})->with([
    ['GET', '/api/me'],
    ['POST', '/api/logout'],
    ['GET', '/api/appraisals'],
    ['POST', '/api/documentos/revision-formato'],
    ['POST', '/api/asistente/consultas'],
]);

test('logout revokes the current token and answers 204', function () {
    $usuario = User::factory()->colaborador()->create();
    $token = $usuario->createToken('asistente-movil')->plainTextToken;

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();

    expect($usuario->tokens()->count())->toBe(0);

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
});

test('a user deactivated after login is rejected and loses the token', function () {
    $usuario = User::factory()->colaborador()->create();
    $token = $usuario->createToken('asistente-movil')->plainTextToken;

    $usuario->update(['is_active' => false]);

    $this->withToken($token)->getJson('/api/me')->assertUnauthorized();

    expect($usuario->tokens()->count())->toBe(0);
});

test('login is throttled after repeated attempts', function () {
    foreach (range(1, 6) as $intento) {
        $this->postJson('/api/login', ['email' => 'x@empresa.com', 'password' => 'mala'])->assertStatus(422);
    }

    $this->postJson('/api/login', ['email' => 'x@empresa.com', 'password' => 'mala'])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});
