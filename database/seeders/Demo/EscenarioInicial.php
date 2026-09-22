<?php

namespace Database\Seeders\Demo;

use App\Models\Appraisal;
use App\Models\AuditLog;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\Gap;
use App\Models\PracticeEvaluation;
use App\Models\Rol;
use Database\Seeders\EscenariosDemoSeeder as Demo;
use Illuminate\Database\Seeder;

class EscenarioInicial extends Seeder
{
    public function run(): void
    {
        $gestor = Demo::usuario('javier.camacho@dima.bo', 'Javier Camacho Rivero', Rol::GESTOR_PROCESOS);
        $jefe = Demo::usuario('sergio.villarroel@dima.bo', 'Sergio Villarroel Careaga', Rol::JEFE_PROYECTO);
        $colaboradora = Demo::usuario('noelia.chavez@dima.bo', 'Noelia Chávez Suárez', Rol::COLABORADOR);
        $administrador = Demo::usuario('admin@dima.bo', 'Patricia Vargas Ledezma', Rol::ADMINISTRADOR);

        $proyecto = Demo::proyecto(
            'PRJ-PORT-03',
            'Portal de Atención al Cliente',
            '2026-08-03',
            [$gestor, $jefe, $colaboradora]
        );

        $appraisal = Demo::appraisal($proyecto, 'Appraisal CMMI Nivel 2 - Portal 2026', 2, '2027-06-30', Appraisal::STATUS_BORRADOR, [1, 2]);

        Demo::evidencia($proyecto, 'Acta de reunión de arranque del portal', Evidence::TYPE_ACTA, Demo::estadoEvidencia('registrada'), $jefe, ['PLAN 1.1'], '2026-08-14');
        Demo::evidencia($proyecto, 'Lista preliminar de necesidades del área de atención', Evidence::TYPE_REGISTRO, Demo::estadoEvidencia('registrada'), $colaboradora, ['RDM 1.1'], '2026-09-01');
        Demo::evidencia($proyecto, 'Cronograma tentativo de la primera fase', Evidence::TYPE_PLAN, Demo::estadoEvidencia('registrada'), $jefe, ['PLAN 1.1'], '2026-09-17');

        Demo::evaluar($appraisal, 'PLAN 1.1', [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_PENDIENTE], $gestor, '2026-09-18 11:40:00');

        $evaluacion = $appraisal->practiceEvaluations()->with('practice')->get()
            ->keyBy(fn (PracticeEvaluation $registro): string => $registro->practice->code);

        Demo::gap($evaluacion['PLAN 1.1'], [
            'title' => 'El plan de trabajo inicial aún no está aprobado por el patrocinador',
            'description' => 'El cronograma tentativo fue elaborado, pero todavía no cuenta con la aprobación formal del patrocinador del portal.',
            'status' => Gap::STATUS_ABIERTO,
            'severity' => Gap::SEVERITY_MEDIA,
            'assigned_to_id' => $jefe->id,
            'generated_by' => $gestor->id,
            'due_date' => '2026-10-24',
        ]);

        Demo::recalcularEstados($appraisal);

        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $proyecto, '2026-08-03 08:15:00', null, ['code' => $proyecto->code, 'name' => $proyecto->name]);
        Demo::bitacora($administrador, AuditLog::ACTION_CREATED, $appraisal, '2026-09-08 10:05:00', null, ['name' => $appraisal->name, 'target_level' => 2]);
    }
}
