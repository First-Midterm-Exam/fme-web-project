<?php

namespace Database\Seeders;

use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProyectoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crea un proyecto y appraisal de demostración para que el equipo pueda probar inmediatamente.
     */
    public function run(): void
    {
        // 1. Crear o recuperar el proyecto demo
        $project = Project::firstOrCreate(
            ['code' => 'PRJ-001'],
            [
                'name' => 'Sistema de Facturación DIMA',
                'start_date' => '2026-09-08',
                'status' => 'activo',
            ]
        );

        // 2. Asignar los usuarios de prueba (Jefe de Proyecto y Colaborador) al proyecto
        $usersToAssign = User::whereIn('email', [
            'jefe.proyecto@dima.com',
            'colaborador@dima.com',
        ])->pluck('id');

        if ($usersToAssign->isNotEmpty()) {
            $project->users()->syncWithoutDetaching($usersToAssign);
        }

        // 3. Crear el appraisal demo en estado borrador
        $appraisal = Appraisal::firstOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Appraisal CMMI Nivel 3 - H1',
            ],
            [
                'domain' => 'Development',
                'target_level' => 3,
                'target_date' => '2026-11-30',
                'status' => 'borrador',
            ]
        );

        // 4. Pre-seleccionar algunas prácticas en el alcance inicial (nivel 1, 2 y 3 de áreas clave)
        $practiceIds = Practice::whereIn('code', [
            'PLAN 1.1', 'PLAN 2.1', 'PLAN 2.2', 'PLAN 3.1',
            'EST 1.1', 'EST 2.1', 'EST 3.1',
            'RDM 1.1', 'RDM 2.1', 'RDM 2.2',
            'TS 1.1', 'TS 2.1',
        ])->pluck('id');

        if ($practiceIds->isNotEmpty()) {
            $appraisal->practices()->syncWithoutDetaching($practiceIds);
        }
    }
}
