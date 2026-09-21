<?php

namespace App\Services\Documentos;

use Illuminate\Http\UploadedFile;

class RevisorFormatoDePrueba implements RevisorFormato
{
    /**
     * @return array<string, mixed>
     */
    public function revisar(UploadedFile $imagen): array
    {
        return [
            'cumple' => false,
            'puntaje' => 72,
            'tipo_detectado' => 'Plan de Proyecto',
            'resumen' => 'El documento tiene la estructura general de un plan de proyecto, pero le faltan secciones obligatorias.',
            'hallazgos' => [
                [
                    'severidad' => 'alta',
                    'elemento' => 'Cronograma',
                    'mensaje' => 'No se encontró la sección de cronograma con hitos y fechas.',
                ],
                [
                    'severidad' => 'media',
                    'elemento' => 'Roles y responsabilidades',
                    'mensaje' => 'La matriz de responsabilidades está incompleta.',
                ],
                [
                    'severidad' => 'baja',
                    'elemento' => 'Control de versiones',
                    'mensaje' => 'No se indica la versión ni la fecha de aprobación del documento.',
                ],
            ],
        ];
    }
}
