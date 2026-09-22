<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    private const USUARIOS = [
        ['email' => 'admin@dima.bo', 'name' => 'Patricia Vargas Ledezma', 'rol' => Rol::ADMINISTRADOR],
        ['email' => 'gestor@dima.bo', 'name' => 'Mariana Salazar Céspedes', 'rol' => Rol::GESTOR_PROCESOS],
        ['email' => 'jefe@dima.bo', 'name' => 'Rodrigo Aliaga Ferrel', 'rol' => Rol::JEFE_PROYECTO],
        ['email' => 'colaborador@dima.bo', 'name' => 'Daniela Ferrufino Áñez', 'rol' => Rol::COLABORADOR],
    ];

    public function run(): void
    {
        foreach (self::USUARIOS as $datos) {
            $usuario = User::updateOrCreate(
                ['email' => $datos['email']],
                [
                    'name' => $datos['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $usuario->syncRoles([Rol::findById($datos['rol'])]);
        }
    }
}
