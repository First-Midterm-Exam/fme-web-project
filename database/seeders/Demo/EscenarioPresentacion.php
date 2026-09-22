<?php

namespace Database\Seeders\Demo;

use App\Models\Appraisal;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\Gap;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\ReadinessMeasurement;
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\EscenariosDemoSeeder as Demo;
use Illuminate\Database\Seeder;

class EscenarioPresentacion extends Seeder
{
    private const PRACTICAS_CONFORMES = [
        'PLAN 1.1', 'PLAN 2.1', 'PLAN 2.2',
        'EST 1.1', 'EST 2.1',
        'RDM 1.1', 'RDM 2.1', 'RDM 2.2',
        'TS 1.1', 'TS 2.1',
        'PR 1.1', 'PR 2.1',
        'VV 1.1',
        'CM 1.1', 'CM 2.1',
        'PQA 1.1',
    ];

    public function run(): void
    {
        $gestora = Demo::usuario('gestor@dima.bo', 'Mariana Salazar Céspedes', Rol::GESTOR_PROCESOS);
        $jefe = Demo::usuario('jefe@dima.bo', 'Rodrigo Aliaga Ferrel', Rol::JEFE_PROYECTO);
        $analista = Demo::usuario('colaborador@dima.bo', 'Daniela Ferrufino Áñez', Rol::COLABORADOR);
        $desarrollador = Demo::usuario('luis.peredo@dima.bo', 'Luis Peredo Montero', Rol::COLABORADOR);
        $administrador = Demo::usuario('admin@dima.bo', 'Patricia Vargas Ledezma', Rol::ADMINISTRADOR);

        $proyecto = Demo::proyecto(
            'PRJ-FACT-01',
            'Sistema de Facturación Electrónica',
            '2025-03-03',
            [$gestora, $jefe, $analista, $desarrollador]
        );

        $cicloAnterior = Demo::appraisal($proyecto, 'Appraisal CMMI Nivel 2 - Ciclo 2025', 2, '2025-11-28', Appraisal::STATUS_CERRADO, [1]);
        $cicloActual = Demo::appraisal($proyecto, 'Appraisal CMMI Nivel 2 - Ciclo 2026', 2, '2026-10-30', Appraisal::STATUS_ACTIVO, [1, 2]);
        $proximoCiclo = Demo::appraisal($proyecto, 'Appraisal CMMI Nivel 3 - Preparación 2027', 3, '2027-05-31', Appraisal::STATUS_BORRADOR, [1, 2, 3]);

        $this->registrarEvidencias($proyecto, $gestora, $jefe, $analista, $desarrollador);
        $this->evaluarCicloAnterior($cicloAnterior, $gestora);
        $this->evaluarCicloActual($cicloActual, $gestora);
        $this->gestionarBrechas($cicloActual, $proyecto, $gestora, $jefe, $analista, $desarrollador);

        Demo::recalcularEstados($cicloAnterior);
        Demo::recalcularEstados($cicloActual);

        Demo::simulacionHistorica($cicloAnterior, 1, $gestora, 100.0, ReadinessMeasurement::STATUS_LISTO, 8, 8, '2025-11-20 09:15:00');
        Demo::simulacionHistorica($cicloActual, 2, $gestora, 61.11, ReadinessMeasurement::STATUS_NO_LISTO, 18, 11, '2026-06-18 10:40:00');
        Demo::simulacionHistorica($cicloActual, 2, $gestora, 77.78, ReadinessMeasurement::STATUS_NO_LISTO, 18, 14, '2026-08-07 16:05:00');
        Demo::simular($cicloActual, 2, $gestora);

        $this->registrarBitacora($administrador, $gestora, $jefe, $proyecto, $cicloActual, $proximoCiclo);
    }

    private function registrarEvidencias(Project $proyecto, User $gestora, User $jefe, User $analista, User $desarrollador): void
    {
        Demo::evidencia($proyecto, 'Plan de proyecto integrado v3', Evidence::TYPE_PLAN, Demo::estadoEvidencia('verificada'), $jefe, ['PLAN 1.1', 'PLAN 2.1', 'PLAN 2.2'], '2026-07-14', $gestora, null, 3);
        Demo::evidencia($proyecto, 'Matriz de estimación por puntos de función', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $analista, ['EST 1.1', 'EST 2.1'], '2026-07-21', $gestora);
        Demo::evidencia($proyecto, 'Catálogo de requerimientos aprobado por el cliente', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $analista, ['RDM 1.1', 'RDM 2.1'], '2026-07-28', $gestora, null, 2);
        Demo::evidencia($proyecto, 'Matriz de trazabilidad de requerimientos', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('verificada'), $analista, ['RDM 2.2'], '2026-08-04', $gestora);
        Demo::evidencia($proyecto, 'Documento de arquitectura y diseño técnico', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $desarrollador, ['TS 1.1', 'TS 2.1'], '2026-08-11', $gestora);
        Demo::evidencia($proyecto, 'Actas de revisión por pares del segundo trimestre', Evidence::TYPE_ACTA, Demo::estadoEvidencia('verificada'), $desarrollador, ['PR 1.1', 'PR 2.1'], '2026-08-18', $gestora);
        Demo::evidencia($proyecto, 'Informe de pruebas de aceptación con el usuario', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $desarrollador, ['VV 1.1', 'VV 2.1'], '2026-08-25', $gestora);
        Demo::evidencia($proyecto, 'Plan de gestión de configuración y línea base', Evidence::TYPE_PLAN, Demo::estadoEvidencia('verificada'), $jefe, ['CM 1.1', 'CM 2.1'], '2026-08-28', $gestora);
        Demo::evidencia($proyecto, 'Informe de auditoría interna de calidad de procesos', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $gestora, ['PQA 1.1', 'PQA 2.1'], '2026-09-01', $gestora, null, 2);
        Demo::evidencia($proyecto, 'Registro de acciones de aseguramiento de calidad', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('observada'), $analista, ['PQA 2.1'], '2026-09-08', $gestora, 'El registro no incluye el seguimiento de las no conformidades de agosto.', 2);
        Demo::evidencia($proyecto, 'Informe de métricas de defectos de revisiones', Evidence::TYPE_INFORME, Demo::estadoEvidencia('rechazada'), $desarrollador, ['PR 2.1'], '2026-09-09', $gestora, 'El informe corresponde a otro proyecto de la organización.');
        Demo::evidencia($proyecto, 'Bitácora de cambios de la línea base de septiembre', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('registrada'), $desarrollador, ['CM 2.1'], '2026-09-15');
        Demo::evidencia($proyecto, 'Acta de cierre de la iteración 8', Evidence::TYPE_ACTA, Demo::estadoEvidencia('registrada'), $jefe, ['PLAN 2.2'], '2026-09-18');
        Demo::evidencia($proyecto, 'Política de calidad y procesos organizacionales', Evidence::TYPE_OTRO, Demo::estadoEvidencia('verificada'), $gestora, ['PQA 1.1'], '2026-04-06', $gestora);
        Demo::evidencia($proyecto, 'Registro de capacitación del equipo en el modelo CMMI', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('verificada'), $gestora, ['PQA 1.1'], '2026-04-20', $gestora);
        Demo::evidencia($proyecto, 'Plan de gestión de riesgos del proyecto', Evidence::TYPE_PLAN, Demo::estadoEvidencia('verificada'), $jefe, ['PLAN 2.2'], '2026-05-04', $gestora);
        Demo::evidencia($proyecto, 'Informes semanales de estado del proyecto', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $jefe, ['PLAN 2.1'], '2026-05-18', $gestora, null, 2);
        Demo::evidencia($proyecto, 'Actas del comité de control de cambios', Evidence::TYPE_ACTA, Demo::estadoEvidencia('verificada'), $analista, ['CM 2.1', 'RDM 2.2'], '2026-06-01', $gestora);
        Demo::evidencia($proyecto, 'Procedimiento de despliegue y control de versiones', Evidence::TYPE_OTRO, Demo::estadoEvidencia('verificada'), $desarrollador, ['CM 1.1'], '2026-06-15', $gestora);
        Demo::evidencia($proyecto, 'Informe de medición y análisis de desempeño', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $gestora, ['EST 2.1'], '2026-06-29', $gestora);
        Demo::evidencia($proyecto, 'Registro de lecciones aprendidas de la iteración 7', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('verificada'), $analista, ['PR 2.1'], '2026-09-05', $gestora);
        Demo::evidencia($proyecto, 'Manual de operación y despliegue del sistema', Evidence::TYPE_OTRO, Demo::estadoEvidencia('verificada'), $desarrollador, ['VV 2.1'], '2026-09-12', $gestora);
    }

    private function evaluarCicloAnterior(Appraisal $appraisal, User $gestora): void
    {
        foreach (['PLAN 1.1', 'EST 1.1', 'RDM 1.1', 'TS 1.1', 'PR 1.1', 'VV 1.1', 'CM 1.1', 'PQA 1.1'] as $codigo) {
            Demo::evaluar($appraisal, $codigo, [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_CUMPLE], $gestora, '2025-11-10 11:00:00');
        }
    }

    private function evaluarCicloActual(Appraisal $appraisal, User $gestora): void
    {
        foreach (self::PRACTICAS_CONFORMES as $codigo) {
            Demo::evaluar($appraisal, $codigo, [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_CUMPLE], $gestora, '2026-09-02 09:30:00');
        }

        Demo::evaluar($appraisal, 'PQA 2.1', [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_NO_CUMPLE], $gestora, '2026-09-10 15:20:00');
        Demo::evaluar($appraisal, 'VV 2.1', [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_PENDIENTE], $gestora, '2026-09-16 10:10:00');
    }

    private function gestionarBrechas(Appraisal $appraisal, Project $proyecto, User $gestora, User $jefe, User $analista, User $desarrollador): void
    {
        $evaluaciones = $appraisal->practiceEvaluations()->with('practice')->get()
            ->keyBy(fn (PracticeEvaluation $evaluacion): string => $evaluacion->practice->code);

        $manual = Evidence::where('project_id', $proyecto->id)
            ->where('name', 'Manual de operación y despliegue del sistema')
            ->first();

        $enProgreso = Demo::gap($evaluaciones['PQA 2.1'], [
            'title' => 'Las no conformidades de calidad no tienen seguimiento documentado',
            'description' => 'La auditoría de procesos detectó que las no conformidades levantadas en agosto no cuentan con registro de seguimiento ni cierre formal.',
            'status' => Gap::STATUS_EN_PROGRESO,
            'severity' => Gap::SEVERITY_ALTA,
            'assigned_to_id' => $jefe->id,
            'generated_by' => $gestora->id,
            'due_date' => '2026-10-10',
            'bitacora' => [
                ['field' => 'status', 'old_value' => Gap::STATUS_ABIERTO, 'new_value' => Gap::STATUS_EN_PROGRESO, 'user_id' => $jefe->id, 'description' => 'El equipo inició el registro del seguimiento de no conformidades.'],
            ],
        ]);

        Demo::accion($enProgreso, [
            'description' => 'Documentar el seguimiento y cierre de las no conformidades de agosto en el registro de calidad',
            'responsible_id' => $analista->id,
            'due_date' => '2026-10-05',
            'progress_percent' => 60,
            'status' => CorrectiveAction::STATUS_EN_PROGRESO,
            'bitacora' => [
                ['field' => 'progress_percent', 'old_value' => '0', 'new_value' => '30', 'user_id' => $analista->id, 'description' => 'Se consolidó el listado de no conformidades pendientes.'],
                ['field' => 'progress_percent', 'old_value' => '30', 'new_value' => '60', 'user_id' => $analista->id, 'description' => 'Se documentó el seguimiento de seis de diez no conformidades.'],
            ],
        ]);

        $resuelto = Demo::gap($evaluaciones['VV 2.1'], [
            'title' => 'Falta el manual de operación para la validación en ambiente productivo',
            'description' => 'La práctica de verificación y validación requiere el manual de operación entregado al área usuaria antes del cierre del appraisal.',
            'status' => Gap::STATUS_RESUELTO,
            'severity' => Gap::SEVERITY_MEDIA,
            'assigned_to_id' => $desarrollador->id,
            'generated_by' => $gestora->id,
            'due_date' => '2026-09-30',
            'bitacora' => [
                ['field' => 'status', 'old_value' => Gap::STATUS_EN_PROGRESO, 'new_value' => Gap::STATUS_RESUELTO, 'user_id' => $desarrollador->id, 'description' => 'Se adjuntó el manual de operación como solución de la brecha.'],
            ],
        ]);

        Demo::accion($resuelto, [
            'description' => 'Elaborar y publicar el manual de operación y despliegue del sistema',
            'responsible_id' => $desarrollador->id,
            'due_date' => '2026-09-20',
            'progress_percent' => 100,
            'status' => CorrectiveAction::STATUS_CERRADA,
            'solution_evidence_id' => $manual?->id,
            'bitacora' => [
                ['field' => 'progress_percent', 'old_value' => '50', 'new_value' => '100', 'user_id' => $desarrollador->id, 'description' => 'El manual fue revisado por el área usuaria.'],
            ],
        ]);

        Demo::gap($evaluaciones['CM 2.1'], [
            'title' => 'La bitácora de cambios de línea base está pendiente de verificación',
            'description' => 'La bitácora de septiembre fue registrada pero aún no cuenta con verificación del gestor de procesos.',
            'status' => Gap::STATUS_ABIERTO,
            'severity' => Gap::SEVERITY_BAJA,
            'assigned_to_id' => $desarrollador->id,
            'generated_by' => $gestora->id,
            'due_date' => '2026-10-20',
        ]);

        $verificado = Demo::gap($evaluaciones['PLAN 2.1'], [
            'title' => 'El plan de trabajo no contenía estimaciones actualizadas del segundo trimestre',
            'description' => 'Las estimaciones del plan correspondían a la versión inicial y no reflejaban el avance real del proyecto.',
            'status' => Gap::STATUS_VERIFICADO,
            'severity' => Gap::SEVERITY_ALTA,
            'assigned_to_id' => $jefe->id,
            'generated_by' => $gestora->id,
            'due_date' => '2026-07-15',
            'bitacora' => [
                ['field' => 'status', 'old_value' => Gap::STATUS_RESUELTO, 'new_value' => Gap::STATUS_VERIFICADO, 'user_id' => $gestora->id, 'description' => 'La gestora de procesos validó la versión 3 del plan de proyecto.'],
            ],
        ]);

        Demo::accion($verificado, [
            'description' => 'Actualizar el plan de proyecto con las estimaciones del segundo trimestre',
            'responsible_id' => $jefe->id,
            'due_date' => '2026-07-10',
            'progress_percent' => 100,
            'status' => CorrectiveAction::STATUS_CERRADA,
        ]);

        $cerrado = Demo::gap($evaluaciones['RDM 2.1'], [
            'title' => 'Los requerimientos no funcionales carecían de criterios de aceptación',
            'description' => 'El catálogo de requerimientos no definía criterios medibles para los requerimientos de desempeño y seguridad.',
            'status' => Gap::STATUS_CERRADO,
            'severity' => Gap::SEVERITY_MEDIA,
            'assigned_to_id' => $analista->id,
            'generated_by' => $gestora->id,
            'due_date' => '2026-08-01',
            'closed_by' => $gestora->id,
            'closed_at' => '2026-08-05 17:30:00',
            'bitacora' => [
                ['field' => 'status', 'old_value' => Gap::STATUS_VERIFICADO, 'new_value' => Gap::STATUS_CERRADO, 'user_id' => $gestora->id, 'description' => 'Brecha cerrada formalmente tras la verificación del catálogo.'],
            ],
        ]);

        Demo::accion($cerrado, [
            'description' => 'Definir criterios de aceptación medibles para los requerimientos no funcionales',
            'responsible_id' => $analista->id,
            'due_date' => '2026-07-28',
            'progress_percent' => 100,
            'status' => CorrectiveAction::STATUS_CERRADA,
        ]);
    }

    private function registrarBitacora(User $administrador, User $gestora, User $jefe, Project $proyecto, Appraisal $cicloActual, Appraisal $proximoCiclo): void
    {
        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $jefe, '2025-03-01 08:30:00', null, ['name' => $jefe->name, 'email' => $jefe->email]);
        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $proyecto, '2025-03-03 09:00:00', null, ['code' => $proyecto->code, 'name' => $proyecto->name]);
        Demo::bitacora($administrador, AuditLog::ACTION_MEMBER_ADDED, $proyecto, '2025-03-04 10:15:00', null, ['integrante' => $jefe->email]);
        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $cicloActual, '2026-01-12 11:20:00', null, ['name' => $cicloActual->name, 'target_level' => 2]);
        Demo::bitacora($gestora, AuditLog::ACTION_UPDATED, $cicloActual, '2026-02-02 08:45:00', ['status' => Appraisal::STATUS_BORRADOR], ['status' => Appraisal::STATUS_ACTIVO]);
        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $proximoCiclo, '2026-09-05 09:10:00', null, ['name' => $proximoCiclo->name, 'target_level' => 3]);
    }
}
