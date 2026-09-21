<?php

namespace App\Services\Asistente;

final class CatalogoReportes
{
    public const RESUMEN = 'resumen_appraisal';

    public const CUMPLIMIENTO = 'cumplimiento_practicas';

    public const CRITERIOS = 'criterios_no_cumplidos';

    public const EVIDENCIAS = 'evidencias';

    public const GAPS = 'gaps_criticos';

    public const GENERAL = 'consulta_general';

    public const TIPOS = [
        self::RESUMEN => [
            'titulo' => 'Resumen del appraisal',
            'descripcion' => 'Estado general: alcance, prácticas por estado, porcentaje de cumplimiento y evidencias registradas.',
        ],
        self::CUMPLIMIENTO => [
            'titulo' => 'Cumplimiento por práctica',
            'descripcion' => 'Cada práctica del alcance con su estado de evaluación y porcentaje de criterios cumplidos.',
        ],
        self::CRITERIOS => [
            'titulo' => 'Criterios no cumplidos y pendientes',
            'descripcion' => 'Criterios marcados como no cumplidos o que aún no se han evaluado.',
        ],
        self::EVIDENCIAS => [
            'titulo' => 'Evidencias del proyecto',
            'descripcion' => 'Evidencias registradas para el proyecto del appraisal, con su estado y versión vigente.',
        ],
        self::GAPS => [
            'titulo' => 'Gaps críticos',
            'descripcion' => 'Brechas de severidad alta abiertas, con su práctica y fecha límite.',
        ],
        self::GENERAL => [
            'titulo' => 'Consulta sobre el appraisal',
            'descripcion' => 'Pregunta general sobre el appraisal que no pide un reporte específico.',
        ],
    ];

    public static function existe(string $tipo): bool
    {
        return array_key_exists($tipo, self::TIPOS);
    }

    public static function titulo(string $tipo): string
    {
        return self::TIPOS[$tipo]['titulo'] ?? self::TIPOS[self::GENERAL]['titulo'];
    }
}
