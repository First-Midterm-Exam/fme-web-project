<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'Administrador',
            'Process Manager / CMMI Manager',
            'Project Manager',
            'Contributor',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@dima.cl'],
            [
                'name' => 'Administrador DIMA',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $admin->assignRole('Administrador');
    }
}
