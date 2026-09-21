<?php

namespace Database\Seeders;

use App\Models\Practice;
use App\Models\PracticeArea;
use Illuminate\Database\Seeder;

class CmmiCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * IMPORTANTE / DOCUMENTACIÓN:
     * Este seeder contiene un conjunto de datos de ejemplo representativo y estructurado
     * del catálogo CMMI V3.0 para propósitos de prueba, desarrollo y evaluación en el sistema.
     * No constituye el catálogo oficial completo de ISACA / CMMI Institute.
     */
    public function run(): void
    {
        $catalog = [
            [
                'code' => 'PLAN',
                'name' => 'Planning',
                'practices' => [
                    ['code' => 'PLAN 1.1', 'name' => 'Desarrollar el plan de trabajo básico del proyecto', 'level' => 1],
                    ['code' => 'PLAN 2.1', 'name' => 'Establecer y mantener planes detallados de trabajo y estimaciones', 'level' => 2],
                    ['code' => 'PLAN 2.2', 'name' => 'Identificar recursos y coordinar con los involucrados del proyecto', 'level' => 2],
                    ['code' => 'PLAN 3.1', 'name' => 'Gestionar el proyecto usando planes integrados y procesos organizacionales', 'level' => 3],
                ],
            ],
            [
                'code' => 'EST',
                'name' => 'Estimating',
                'practices' => [
                    ['code' => 'EST 1.1', 'name' => 'Realizar estimaciones básicas de tamaño y esfuerzo', 'level' => 1],
                    ['code' => 'EST 2.1', 'name' => 'Desarrollar estimaciones basadas en datos históricos y métodos estandarizados', 'level' => 2],
                    ['code' => 'EST 3.1', 'name' => 'Utilizar modelos de estimación cuantitativa y calibración periódica', 'level' => 3],
                ],
            ],
            [
                'code' => 'RDM',
                'name' => 'Requirements Development and Management',
                'practices' => [
                    ['code' => 'RDM 1.1', 'name' => 'Registrar necesidades y requerimientos iniciales del cliente', 'level' => 1],
                    ['code' => 'RDM 2.1', 'name' => 'Obtener, analizar y validar requerimientos funcionales y técnicos', 'level' => 2],
                    ['code' => 'RDM 2.2', 'name' => 'Mantener la trazabilidad bidireccional de requerimientos', 'level' => 2],
                    ['code' => 'RDM 3.1', 'name' => 'Desarrollar arquitectura de requerimientos detallados y derivados', 'level' => 3],
                ],
            ],
            [
                'code' => 'TS',
                'name' => 'Technical Solution',
                'practices' => [
                    ['code' => 'TS 1.1', 'name' => 'Diseñar e implementar soluciones técnicas para los requerimientos', 'level' => 1],
                    ['code' => 'TS 2.1', 'name' => 'Evaluar alternativas de diseño y seleccionar soluciones óptimas', 'level' => 2],
                    ['code' => 'TS 3.1', 'name' => 'Desarrollar diseños modulares detallados e interfaces estándar', 'level' => 3],
                ],
            ],
            [
                'code' => 'PR',
                'name' => 'Peer Reviews',
                'practices' => [
                    ['code' => 'PR 1.1', 'name' => 'Realizar revisiones informales de productos de trabajo', 'level' => 1],
                    ['code' => 'PR 2.1', 'name' => 'Planificar y ejecutar revisiones por pares estructuradas', 'level' => 2],
                    ['code' => 'PR 3.1', 'name' => 'Analizar métricas de defectos de revisiones para mejorar la calidad', 'level' => 3],
                ],
            ],
            [
                'code' => 'VV',
                'name' => 'Verification and Validation',
                'practices' => [
                    ['code' => 'VV 1.1', 'name' => 'Probar y verificar componentes básicos del producto', 'level' => 1],
                    ['code' => 'VV 2.1', 'name' => 'Ejecutar pruebas formales según criterios de aceptación definidos', 'level' => 2],
                    ['code' => 'VV 3.1', 'name' => 'Validar el producto final en el entorno operativo del usuario', 'level' => 3],
                ],
            ],
            [
                'code' => 'PQA',
                'name' => 'Process Quality Assurance',
                'practices' => [
                    ['code' => 'PQA 1.1', 'name' => 'Monitorear actividades y productos de trabajo del proyecto', 'level' => 1],
                    ['code' => 'PQA 2.1', 'name' => 'Evaluar objetivamente procesos y productos contra estándares aplicables', 'level' => 2],
                    ['code' => 'PQA 3.1', 'name' => 'Identificar y registrar no conformidades y tendencias de calidad', 'level' => 3],
                ],
            ],
            [
                'code' => 'CM',
                'name' => 'Configuration Management',
                'practices' => [
                    ['code' => 'CM 1.1', 'name' => 'Controlar versiones de elementos de configuración básicos', 'level' => 1],
                    ['code' => 'CM 2.1', 'name' => 'Establecer líneas base y controlar solicitudes de cambio', 'level' => 2],
                    ['code' => 'CM 3.1', 'name' => 'Realizar auditorías de configuración funcional y física', 'level' => 3],
                ],
            ],
        ];

        foreach ($catalog as $areaData) {
            $area = PracticeArea::firstOrCreate(
                ['code' => $areaData['code']],
                ['name' => $areaData['name']]
            );

            foreach ($areaData['practices'] as $practiceData) {
                Practice::firstOrCreate(
                    ['code' => $practiceData['code']],
                    [
                        'practice_area_id' => $area->id,
                        'name' => $practiceData['name'],
                        'level' => $practiceData['level'],
                    ]
                );
            }
        }
    }
}
