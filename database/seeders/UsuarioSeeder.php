<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    private const USUARIOS = [
        ['email' => 'admin@dima.cl', 'name' => 'Administrador DIMA', 'rol' => Rol::ADMINISTRADOR],
        ['email' => 'gestor@dima.cl', 'name' => 'Gestor de Procesos DIMA', 'rol' => Rol::GESTOR_PROCESOS],
        ['email' => 'jefe@dima.cl', 'name' => 'Jefe de Proyecto DIMA', 'rol' => Rol::JEFE_PROYECTO],
        ['email' => 'colaborador@dima.cl', 'name' => 'Colaborador DIMA', 'rol' => Rol::COLABORADOR],
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
