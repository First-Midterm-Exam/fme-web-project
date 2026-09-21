<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function administrador(): static
    {
        return $this->conRol(Rol::ADMINISTRADOR);
    }

    public function gestorProcesos(): static
    {
        return $this->conRol(Rol::GESTOR_PROCESOS);
    }

    public function jefeProyecto(): static
    {
        return $this->conRol(Rol::JEFE_PROYECTO);
    }

    public function colaborador(): static
    {
        return $this->conRol(Rol::COLABORADOR);
    }

    public function conRol(int $rolId): static
    {
        return $this->afterCreating(function (User $user) use ($rolId): void {
            $user->syncRoles([Rol::findById($rolId)]);
        });
    }
}
