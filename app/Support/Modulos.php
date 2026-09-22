<?php

namespace App\Support;

final class Modulos
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function grupos(): array
    {
        return [
            [
                'etiqueta' => 'Proyectos',
                'icono' => 'bi-folder2-open',
                'items' => [
                    [
                        'etiqueta' => 'Proyectos',
                        'descripcion' => 'Proyectos de la empresa.',
                        'icono' => 'bi-folder2-open',
                        'uri' => 'proyectos',
                        'ruta' => 'proyectos.index',
                        'capacidad' => Capacidades::VER_PROYECTOS,
                        'disponible' => true,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Appraisals',
                'icono' => 'bi-clipboard-check',
                'items' => [
                    [
                        'etiqueta' => 'Appraisals',
                        'descripcion' => 'Evaluaciones CMMI sobre proyectos.',
                        'icono' => 'bi-clipboard-check',
                        'uri' => 'appraisals',
                        'ruta' => 'appraisals.index',
                        'capacidad' => Capacidades::VER_APPRAISALS,
                        'disponible' => true,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Administración',
                'icono' => 'bi-sliders',
                'items' => [
                    [
                        'etiqueta' => 'Usuarios',
                        'descripcion' => 'Acceso y roles del equipo.',
                        'icono' => 'bi-people',
                        'uri' => 'users',
                        'ruta' => 'users.index',
                        'capacidad' => Capacidades::GESTIONAR_USUARIOS,
                        'disponible' => true,
                    ],
                    [
                        'etiqueta' => 'Bitácora',
                        'descripcion' => 'Quién cambió qué y cuándo en la plataforma.',
                        'icono' => 'bi-journal-text',
                        'uri' => 'bitacora',
                        'ruta' => 'bitacora.index',
                        'capacidad' => Capacidades::VER_BITACORA,
                        'disponible' => true,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Alcance CMMI',
                'icono' => 'bi-diagram-3',
                'items' => [
                    [
                        'etiqueta' => 'Áreas de Práctica',
                        'descripcion' => 'Practice Areas incluidas en el alcance.',
                        'icono' => 'bi-grid-3x3-gap',
                        'uri' => 'alcance/areas',
                        'ruta' => 'alcance.areas',
                        'capacidad' => Capacidades::DEFINIR_ALCANCE,
                        'disponible' => false,
                    ],
                    [
                        'etiqueta' => 'Prácticas',
                        'descripcion' => 'Prácticas del catálogo CMMI.',
                        'icono' => 'bi-list-check',
                        'uri' => 'alcance/practicas',
                        'ruta' => 'alcance.practicas',
                        'capacidad' => Capacidades::DEFINIR_ALCANCE,
                        'disponible' => false,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Cumplimiento',
                'icono' => 'bi-ui-checks',
                'items' => [
                    [
                        'etiqueta' => 'Evaluación de Prácticas',
                        'descripcion' => 'Estado de cumplimiento por práctica.',
                        'icono' => 'bi-check2-square',
                        'uri' => 'cumplimiento/evaluacion',
                        'ruta' => 'cumplimiento.evaluacion',
                        'capacidad' => Capacidades::EVALUAR_CUMPLIMIENTO,
                        'disponible' => false,
                    ],
                    [
                        'etiqueta' => 'Criterios',
                        'descripcion' => 'Criterios objetivos de cada práctica.',
                        'icono' => 'bi-rulers',
                        'uri' => 'cumplimiento/criterios',
                        'ruta' => 'cumplimiento.criterios',
                        'capacidad' => Capacidades::EVALUAR_CUMPLIMIENTO,
                        'disponible' => false,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Evidencias',
                'icono' => 'bi-paperclip',
                'items' => [
                    [
                        'etiqueta' => 'Registro de Evidencias',
                        'descripcion' => 'Evidencias con archivo, metadata y versiones.',
                        'icono' => 'bi-file-earmark-arrow-up',
                        'uri' => 'evidencias',
                        'ruta' => 'evidencias.index',
                        'capacidad' => Capacidades::REGISTRAR_EVIDENCIA,
                        'disponible' => true,
                    ],
                    [
                        'etiqueta' => 'Verificación',
                        'descripcion' => 'Revisión de evidencias pendientes.',
                        'icono' => 'bi-patch-check',
                        'uri' => 'evidencias/verificacion',
                        'ruta' => 'evidencias.verificacion',
                        'capacidad' => Capacidades::VERIFICAR_EVIDENCIAS,
                        'disponible' => true,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Gaps y Acciones',
                'icono' => 'bi-exclamation-triangle',
                'items' => [
                    [
                        'etiqueta' => 'Gaps',
                        'descripcion' => 'Brechas con severidad y responsable.',
                        'icono' => 'bi-bug',
                        'uri' => 'gaps',
                        'ruta' => 'gaps.index',
                        'capacidad' => Capacidades::GESTIONAR_GAPS,
                        'disponible' => true,
                    ],
                    [
                        'etiqueta' => 'Acciones Correctivas',
                        'descripcion' => 'Responsables, avance y cierre.',
                        'icono' => 'bi-tools',
                        'uri' => 'gaps/acciones',
                        'ruta' => 'gaps.acciones',
                        'capacidad' => Capacidades::GESTIONAR_GAPS,
                        'disponible' => false,
                    ],
                    [
                        'etiqueta' => 'Validación de Cierre',
                        'descripcion' => 'Verificación y cierre formal de gaps resueltos.',
                        'icono' => 'bi-patch-check',
                        'uri' => 'gaps/validacion',
                        'ruta' => 'gaps.validacion',
                        'capacidad' => Capacidades::GESTIONAR_GAPS,
                        'disponible' => true,
                    ],
                ],
            ],
            [
                'etiqueta' => 'Reportes',
                'icono' => 'bi-graph-up-arrow',
                'items' => [
                    [
                        'etiqueta' => 'Readiness Score',
                        'descripcion' => 'Puntaje de preparación y su desglose.',
                        'icono' => 'bi-speedometer',
                        'uri' => 'readiness',
                        'ruta' => 'readiness.index',
                        'capacidad' => Capacidades::VER_READINESS,
                        'disponible' => false,
                    ],
                    [
                        'etiqueta' => 'Trazabilidad',
                        'descripcion' => 'Cadena práctica, evidencia, gap y acción.',
                        'icono' => 'bi-bezier2',
                        'uri' => 'readiness/trazabilidad',
                        'ruta' => 'readiness.trazabilidad',
                        'capacidad' => Capacidades::VER_READINESS,
                        'disponible' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function items(): array
    {
        return array_merge(...array_column(self::grupos(), 'items'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pendientes(): array
    {
        return array_values(array_filter(self::items(), fn (array $item): bool => $item['disponible'] === false));
    }
}
