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
use App\Models\Rol;
use App\Models\User;
use Database\Seeders\EscenariosDemoSeeder as Demo;
use Illuminate\Database\Seeder;

class EscenarioIntermedio extends Seeder
{
    public function run(): void
    {
        $gestor = Demo::usuario('javier.camacho@dima.bo', 'Javier Camacho Rivero', Rol::GESTOR_PROCESOS);
        $jefa = Demo::usuario('carla.montano@dima.bo', 'Carla Montaño Villegas', Rol::JEFE_PROYECTO);
        $colaborador = Demo::usuario('ivan.quispe@dima.bo', 'Iván Quispe Mamani', Rol::COLABORADOR);
        $administrador = Demo::usuario('admin@dima.bo', 'Patricia Vargas Ledezma', Rol::ADMINISTRADOR);

        $proyecto = Demo::proyecto(
            'PRJ-LOG-02',
            'Plataforma de Gestión Logística',
            '2026-01-15',
            [$gestor, $jefa, $colaborador]
        );

        $appraisal = Demo::appraisal($proyecto, 'Appraisal CMMI Nivel 2 - Logística 2026', 2, '2027-03-31', Appraisal::STATUS_ACTIVO, [1, 2]);

        $this->registrarEvidencias($proyecto, $gestor, $jefa, $colaborador);
        $this->evaluarPracticas($appraisal, $gestor);
        $this->gestionarBrechas($appraisal, $gestor, $jefa, $colaborador);

        Demo::recalcularEstados($appraisal);
        Demo::simular($appraisal, 2, $gestor);

        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $proyecto, '2026-01-15 08:40:00', null, ['code' => $proyecto->code, 'name' => $proyecto->name]);
        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $appraisal, '2026-02-03 09:25:00', null, ['name' => $appraisal->name, 'target_level' => 2]);
        Demo::bitacora($gestor, AuditLog::ACTION_UPDATED, $appraisal, '2026-02-20 14:10:00', ['status' => Appraisal::STATUS_BORRADOR], ['status' => Appraisal::STATUS_ACTIVO]);
    }

    private function registrarEvidencias(Project $proyecto, User $gestor, User $jefa, User $colaborador): void
    {
        Demo::evidencia($proyecto, 'Plan de trabajo de la plataforma logística v1', Evidence::TYPE_PLAN, Demo::estadoEvidencia('verificada'), $jefa, ['PLAN 1.1', 'PLAN 2.1'], '2026-04-10', $gestor);
        Demo::evidencia($proyecto, 'Estimación inicial de esfuerzo por módulo', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $jefa, ['EST 1.1'], '2026-05-08', $gestor);
        Demo::evidencia($proyecto, 'Especificación de requerimientos de distribución', Evidence::TYPE_INFORME, Demo::estadoEvidencia('verificada'), $colaborador, ['RDM 1.1', 'RDM 2.1'], '2026-06-12', $gestor, null, 2);
        Demo::evidencia($proyecto, 'Diseño de integración con transportistas', Evidence::TYPE_INFORME, Demo::estadoEvidencia('registrada'), $colaborador, ['TS 1.1'], '2026-08-20');
        Demo::evidencia($proyecto, 'Plan de pruebas de integración', Evidence::TYPE_PLAN, Demo::estadoEvidencia('registrada'), $colaborador, ['VV 1.1'], '2026-09-04');
        Demo::evidencia($proyecto, 'Checklist de revisión por pares de la iteración 3', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('observada'), $colaborador, ['PR 1.1'], '2026-09-10', $gestor, 'El checklist no registra participantes ni hallazgos de la revisión.');
        Demo::evidencia($proyecto, 'Línea base de configuración del repositorio', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('verificada'), $colaborador, ['CM 1.1'], '2026-07-18', $gestor);
    }

    private function evaluarPracticas(Appraisal $appraisal, User $gestor): void
    {
        foreach (['PLAN 1.1', 'EST 1.1', 'RDM 1.1', 'TS 1.1', 'CM 1.1'] as $codigo) {
            Demo::evaluar($appraisal, $codigo, [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_CUMPLE], $gestor, '2026-07-22 10:00:00');
        }

        foreach (['PLAN 2.1', 'RDM 2.1', 'VV 1.1'] as $codigo) {
            Demo::evaluar($appraisal, $codigo, [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_NO_CUMPLE], $gestor, '2026-08-26 11:30:00');
        }

        Demo::evaluar($appraisal, 'PR 1.1', [CriterionCheck::STATUS_NO_CUMPLE, CriterionCheck::STATUS_NO_CUMPLE], $gestor, '2026-09-11 16:45:00');
        Demo::evaluar($appraisal, 'PQA 1.1', [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_PENDIENTE], $gestor, '2026-09-15 09:20:00');
    }

    private function gestionarBrechas(Appraisal $appraisal, User $gestor, User $jefa, User $colaborador): void
    {
        $evaluaciones = $appraisal->practiceEvaluations()->with('practice')->get()
            ->keyBy(fn (PracticeEvaluation $evaluacion): string => $evaluacion->practice->code);

        Demo::gap($evaluaciones['PR 1.1'], [
            'title' => 'No se ejecutan revisiones por pares de los productos de trabajo',
            'description' => 'El proyecto no cuenta con evidencia de revisiones por pares planificadas ni ejecutadas en las iteraciones entregadas.',
            'status' => Gap::STATUS_ABIERTO,
            'severity' => Gap::SEVERITY_CRITICA,
            'assigned_to_id' => $jefa->id,
            'generated_by' => $gestor->id,
            'due_date' => '2026-11-14',
        ]);

        $pruebas = Demo::gap($evaluaciones['VV 1.1'], [
            'title' => 'Las pruebas de integración no tienen criterios de aceptación definidos',
            'description' => 'El plan de pruebas no define los criterios de aceptación ni los responsables de la ejecución por módulo.',
            'status' => Gap::STATUS_EN_PROGRESO,
            'severity' => Gap::SEVERITY_ALTA,
            'assigned_to_id' => $colaborador->id,
            'generated_by' => $gestor->id,
            'due_date' => '2026-10-31',
            'bitacora' => [
                ['field' => 'status', 'old_value' => Gap::STATUS_ABIERTO, 'new_value' => Gap::STATUS_EN_PROGRESO, 'user_id' => $colaborador->id, 'description' => 'Se comenzó la definición de criterios de aceptación por módulo.'],
            ],
        ]);

        Demo::accion($pruebas, [
            'description' => 'Completar el plan de pruebas con criterios de aceptación y responsables por módulo',
            'responsible_id' => $colaborador->id,
            'due_date' => '2026-10-24',
            'progress_percent' => 40,
            'status' => CorrectiveAction::STATUS_EN_PROGRESO,
            'bitacora' => [
                ['field' => 'progress_percent', 'old_value' => '0', 'new_value' => '40', 'user_id' => $colaborador->id, 'description' => 'Se definieron los criterios de los módulos de rutas y despacho.'],
            ],
        ]);

        $plan = Demo::gap($evaluaciones['PLAN 2.1'], [
            'title' => 'El plan detallado no incorpora el seguimiento de hitos del segundo semestre',
            'description' => 'El plan de trabajo no fue actualizado con los hitos comprometidos con el cliente para el segundo semestre.',
            'status' => Gap::STATUS_ABIERTO,
            'severity' => Gap::SEVERITY_ALTA,
            'assigned_to_id' => $jefa->id,
            'generated_by' => $gestor->id,
            'due_date' => '2026-10-17',
        ]);

        Demo::accion($plan, [
            'description' => 'Actualizar el plan detallado con los hitos y el seguimiento del segundo semestre',
            'responsible_id' => $jefa->id,
            'due_date' => '2026-10-15',
            'progress_percent' => 20,
            'status' => CorrectiveAction::STATUS_EN_PROGRESO,
        ]);

        Demo::gap($evaluaciones['RDM 2.1'], [
            'title' => 'Los requerimientos de integración no están validados con el cliente',
            'description' => 'Las interfaces con los transportistas fueron especificadas sin acta de validación del cliente.',
            'status' => Gap::STATUS_EN_PROGRESO,
            'severity' => Gap::SEVERITY_MEDIA,
            'assigned_to_id' => $colaborador->id,
            'generated_by' => $gestor->id,
            'due_date' => '2026-11-07',
        ]);

        Demo::gap($evaluaciones['CM 1.1'], [
            'title' => 'La línea base no registra los cambios de la iteración 4',
            'description' => 'Los cambios de configuración de la última iteración no fueron incorporados al registro de línea base.',
            'status' => Gap::STATUS_ABIERTO,
            'severity' => Gap::SEVERITY_BAJA,
            'assigned_to_id' => $colaborador->id,
            'generated_by' => $gestor->id,
            'due_date' => '2026-12-05',
        ]);
    }
}
