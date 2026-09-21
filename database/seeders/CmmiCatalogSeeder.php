<?php

namespace Database\Seeders;

use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeCriterion;
use Illuminate\Database\Seeder;

class CmmiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            [
                'code' => 'PLAN',
                'name' => 'Planning',
                'practices' => [
                    [
                        'code' => 'PLAN 1.1',
                        'name' => 'Desarrollar el plan de trabajo básico del proyecto',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'PLAN 1.1-C1',
                                'description' => 'Existe un cronograma inicial con actividades, hitos y fechas tentativas aprobado.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PLAN 1.1-C2',
                                'description' => 'Se identifican los roles y responsabilidades básicas asignados al equipo del proyecto.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PLAN 2.1',
                        'name' => 'Establecer y mantener planes detallados de trabajo y estimaciones',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'PLAN 2.1-C1',
                                'description' => 'El plan de trabajo desglosa la EDT/WBS y define dependencias entre tareas.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PLAN 2.1-C2',
                                'description' => 'El plan incluye presupuesto, cronograma y supuestos de planificación documentados.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PLAN 2.2',
                        'name' => 'Identificar recursos y coordinar con los involucrados del proyecto',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'PLAN 2.2-C1',
                                'description' => 'Se cuenta con un plan de gestión de interesados y matriz de comunicaciones aprobada.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PLAN 2.2-C2',
                                'description' => 'Se verifica la asignación y disponibilidad formal de los recursos requeridos.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PLAN 3.1',
                        'name' => 'Gestionar el proyecto usando planes integrados y procesos organizacionales',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'PLAN 3.1-C1',
                                'description' => 'El plan del proyecto integra los procesos organizacionales estándar adaptados (tailoring).',
                                'required' => true,
                            ],
                            [
                                'code' => 'PLAN 3.1-C2',
                                'description' => 'Se mantienen registros de seguimiento periódico y actualización de los planes integrados.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'EST',
                'name' => 'Estimating',
                'practices' => [
                    [
                        'code' => 'EST 1.1',
                        'name' => 'Realizar estimaciones básicas de tamaño y esfuerzo',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'EST 1.1-C1',
                                'description' => 'Se documentan estimaciones de esfuerzo y duración para los componentes principales.',
                                'required' => true,
                            ],
                            [
                                'code' => 'EST 1.1-C2',
                                'description' => 'Los supuestos utilizados para la estimación inicial están registrados explícitamente.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'EST 2.1',
                        'name' => 'Desarrollar estimaciones basadas en datos históricos y métodos estandarizados',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'EST 2.1-C1',
                                'description' => 'Se aplican métodos de estimación estructurados (story points o puntos de función).',
                                'required' => true,
                            ],
                            [
                                'code' => 'EST 2.1-C2',
                                'description' => 'Las estimaciones se contrastan y justifican con datos históricos de proyectos previos.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'EST 3.1',
                        'name' => 'Utilizar modelos de estimación cuantitativa y calibración periódica',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'EST 3.1-C1',
                                'description' => 'Se emplean modelos o fórmulas estadísticas calibradas para calcular tamaño y costo.',
                                'required' => true,
                            ],
                            [
                                'code' => 'EST 3.1-C2',
                                'description' => 'Se realiza análisis de sensibilidad y rangos de incertidumbre en las estimaciones.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'RDM',
                'name' => 'Requirements Development and Management',
                'practices' => [
                    [
                        'code' => 'RDM 1.1',
                        'name' => 'Registrar necesidades y requerimientos iniciales del cliente',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'RDM 1.1-C1',
                                'description' => 'Existe una especificación o lista inicial de necesidades del cliente aprobada.',
                                'required' => true,
                            ],
                            [
                                'code' => 'RDM 1.1-C2',
                                'description' => 'Cada requerimiento inicial cuenta con identificador único y descripción comprensible.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'RDM 2.1',
                        'name' => 'Obtener, analizar y validar requerimientos funcionales y técnicos',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'RDM 2.1-C1',
                                'description' => 'Los requerimientos detallados están clasificados en funcionales, técnicos y no funcionales.',
                                'required' => true,
                            ],
                            [
                                'code' => 'RDM 2.1-C2',
                                'description' => 'Se documentan criterios de aceptación verificables para cada requerimiento aprobado.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'RDM 2.2',
                        'name' => 'Mantener la trazabilidad bidireccional de requerimientos',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'RDM 2.2-C1',
                                'description' => 'Existe una matriz de trazabilidad bidireccional entre requerimientos, diseño y pruebas.',
                                'required' => true,
                            ],
                            [
                                'code' => 'RDM 2.2-C2',
                                'description' => 'Los cambios en requerimientos se reflejan y actualizan en los productos de trabajo derivados.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'RDM 3.1',
                        'name' => 'Desarrollar arquitectura de requerimientos detallados y derivados',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'RDM 3.1-C1',
                                'description' => 'Se definen requerimientos derivados de arquitectura, interfaces externas y seguridad.',
                                'required' => true,
                            ],
                            [
                                'code' => 'RDM 3.1-C2',
                                'description' => 'Se validan los requerimientos derivados contra las restricciones del negocio y operativas.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'TS',
                'name' => 'Technical Solution',
                'practices' => [
                    [
                        'code' => 'TS 1.1',
                        'name' => 'Diseñar e implementar soluciones técnicas para los requerimientos',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'TS 1.1-C1',
                                'description' => 'Existe documentación técnica del diseño preliminar que cubre los requerimientos acordados.',
                                'required' => true,
                            ],
                            [
                                'code' => 'TS 1.1-C2',
                                'description' => 'El código o componente implementado cumple con la especificación de diseño.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'TS 2.1',
                        'name' => 'Evaluar alternativas de diseño y seleccionar soluciones óptimas',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'TS 2.1-C1',
                                'description' => 'Se documentan matrices de decisión o análisis de compensación entre alternativas.',
                                'required' => true,
                            ],
                            [
                                'code' => 'TS 2.1-C2',
                                'description' => 'Se seleccionan tecnologías y patrones arquitectónicos fundamentados en criterios técnicos.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'TS 3.1',
                        'name' => 'Desarrollar diseños modulares detallados e interfaces estándar',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'TS 3.1-C1',
                                'description' => 'Se especifican contratos de interfaz (APIs, esquemas de datos) estandarizados y versionados.',
                                'required' => true,
                            ],
                            [
                                'code' => 'TS 3.1-C2',
                                'description' => 'El diseño modular minimiza el acoplamiento y maximiza la reutilización de componentes.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'PR',
                'name' => 'Peer Reviews',
                'practices' => [
                    [
                        'code' => 'PR 1.1',
                        'name' => 'Realizar revisiones informales de productos de trabajo',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'PR 1.1-C1',
                                'description' => 'Se evidencia la revisión de productos de trabajo clave antes de su integración o entrega.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PR 1.1-C2',
                                'description' => 'Las observaciones encontradas en revisiones informales son comunicadas y atendidas.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PR 2.1',
                        'name' => 'Planificar y ejecutar revisiones por pares estructuradas',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'PR 2.1-C1',
                                'description' => 'Existe un procedimiento formal con roles (moderador, autor, revisor) y lista de chequeo.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PR 2.1-C2',
                                'description' => 'Se genera un acta de revisión formal registrando participantes, defectos y acuerdos.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PR 3.1',
                        'name' => 'Analizar métricas de defectos de revisiones para mejorar la calidad',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'PR 3.1-C1',
                                'description' => 'Se registran y clasifican métricas de defectos (tipo, severidad, fase de inyección).',
                                'required' => true,
                            ],
                            [
                                'code' => 'PR 3.1-C2',
                                'description' => 'Se realizan análisis causales para prevenir defectos recurrentes en revisiones futuras.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'VV',
                'name' => 'Verification and Validation',
                'practices' => [
                    [
                        'code' => 'VV 1.1',
                        'name' => 'Probar y verificar componentes básicos del producto',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'VV 1.1-C1',
                                'description' => 'Se definen y ejecutan casos de prueba unitarios y de integración para componentes básicos.',
                                'required' => true,
                            ],
                            [
                                'code' => 'VV 1.1-C2',
                                'description' => 'Los resultados de ejecución y defectos detectados son registrados en el sistema.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'VV 2.1',
                        'name' => 'Ejecutar pruebas formales según criterios de aceptación definidos',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'VV 2.1-C1',
                                'description' => 'Los casos de prueba cubren los criterios de aceptación especificados en los requerimientos.',
                                'required' => true,
                            ],
                            [
                                'code' => 'VV 2.1-C2',
                                'description' => 'Existe evidencia de pruebas de regresión y cobertura mínima establecida para el release.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'VV 3.1',
                        'name' => 'Validar el producto final en el entorno operativo del usuario',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'VV 3.1-C1',
                                'description' => 'Se ejecutan pruebas de aceptación de usuario (UAT) en entorno homólogo a producción.',
                                'required' => true,
                            ],
                            [
                                'code' => 'VV 3.1-C2',
                                'description' => 'Se obtiene conformidad formal del cliente o usuario final sobre la entrega.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'PQA',
                'name' => 'Process Quality Assurance',
                'practices' => [
                    [
                        'code' => 'PQA 1.1',
                        'name' => 'Monitorear actividades y productos de trabajo del proyecto',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'PQA 1.1-C1',
                                'description' => 'Se realizan revisiones periódicas del cumplimiento de actividades contra el plan de calidad.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PQA 1.1-C2',
                                'description' => 'Se verifica que los productos de trabajo generados sigan los estándares institucionales.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PQA 2.1',
                        'name' => 'Evaluar objetivamente procesos y productos contra estándares aplicables',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'PQA 2.1-C1',
                                'description' => 'Las evaluaciones objetivas se efectúan usando listas de comprobación estandarizadas.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PQA 2.1-C2',
                                'description' => 'Los hallazgos y no conformidades son comunicados a los líderes de proyecto oportunamente.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PQA 3.1',
                        'name' => 'Identificar y registrar no conformidades y tendencias de calidad',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'PQA 3.1-C1',
                                'description' => 'Se mantiene un registro de no conformidades con acciones correctivas y seguimiento de cierre.',
                                'required' => true,
                            ],
                            [
                                'code' => 'PQA 3.1-C2',
                                'description' => 'Se generan informes periódicos de tendencias de calidad para la gerencia de procesos.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'CM',
                'name' => 'Configuration Management',
                'practices' => [
                    [
                        'code' => 'CM 1.1',
                        'name' => 'Controlar versiones de elementos de configuración básicos',
                        'level' => 1,
                        'criteria' => [
                            [
                                'code' => 'CM 1.1-C1',
                                'description' => 'Los productos de trabajo y código fuente residen en un repositorio con control de versiones.',
                                'required' => true,
                            ],
                            [
                                'code' => 'CM 1.1-C2',
                                'description' => 'Se aplican convenciones de etiquetado y ramas acordadas para el proyecto.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'CM 2.1',
                        'name' => 'Establecer líneas base y controlar solicitudes de cambio',
                        'level' => 2,
                        'criteria' => [
                            [
                                'code' => 'CM 2.1-C1',
                                'description' => 'Se definen y congelan líneas base formales en hitos clave del proyecto.',
                                'required' => true,
                            ],
                            [
                                'code' => 'CM 2.1-C2',
                                'description' => 'Las solicitudes de cambio pasan por un flujo de aprobación antes de su implementación.',
                                'required' => true,
                            ],
                        ],
                    ],
                    [
                        'code' => 'CM 3.1',
                        'name' => 'Realizar auditorías de configuración funcional y física',
                        'level' => 3,
                        'criteria' => [
                            [
                                'code' => 'CM 3.1-C1',
                                'description' => 'Se verifica en auditoría física que la línea base contenga todos los elementos requeridos.',
                                'required' => true,
                            ],
                            [
                                'code' => 'CM 3.1-C2',
                                'description' => 'Se verifica en auditoría funcional que el producto cumpla los requerimientos especificados.',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($catalog as $areaData) {
            $area = PracticeArea::firstOrCreate(
                ['code' => $areaData['code']],
                ['name' => $areaData['name']]
            );

            foreach ($areaData['practices'] as $practiceData) {
                $practice = Practice::firstOrCreate(
                    ['code' => $practiceData['code']],
                    [
                        'practice_area_id' => $area->id,
                        'name' => $practiceData['name'],
                        'level' => $practiceData['level'],
                    ]
                );

                foreach ($practiceData['criteria'] ?? [] as $criterionData) {
                    PracticeCriterion::updateOrCreate(
                        [
                            'practice_id' => $practice->id,
                            'code' => $criterionData['code'],
                        ],
                        [
                            'description' => $criterionData['description'],
                            'required' => $criterionData['required'] ?? true,
                        ]
                    );
                }
            }
        }
    }
}
