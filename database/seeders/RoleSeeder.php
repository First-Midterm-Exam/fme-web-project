<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Rol::ETIQUETAS as $id => $nombre) {
            Rol::updateOrCreate(
                ['id' => $id],
                ['name' => $nombre, 'guard_name' => 'web']
            );
        }
    }
}
