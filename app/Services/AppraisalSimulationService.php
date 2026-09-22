<?php

namespace App\Services;

use App\DataTransferObjects\ResultadoSimulacionDTO;
use App\Models\AppraisalSimulation;
use App\Models\CriterionCheck;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeEvaluation;
use App\Models\ReadinessMeasurement;
use App\Models\User;
use App\Repositories\AppraisalRepository;
use App\Repositories\EvaluationRepository;
use Illuminate\Support\Facades\DB;

class AppraisalSimulationService
{
    public const TIPO_ALCANCE = 'Alcance';

    public const TIPO_EVALUACION = 'Evaluación';

    public const TIPO_CRITERIO = 'Criterio';

    public const TIPO_EVIDENCIA = 'Evidencia';

    public const TIPO_GAP = 'Gap';

    public const SEVERIDADES_BLOQUEANTES = [Gap::SEVERITY_ALTA, Gap::SEVERITY_CRITICA];

    public function __construct(
        private readonly AppraisalRepository $appraisalRepository,
        private readonly EvaluationRepository $evaluationRepository,
    ) {}

    public function ejecutarSimulacion(int $appraisalId, int $nivelObjetivo, ?User $usuario = null): ResultadoSimulacionDTO
    {
        $appraisal = $this->appraisalRepository->findAppraisalConAlcance($appraisalId);
        $datos = $this->evaluationRepository->findEvaluacionesPorAppraisal($appraisalId);

        $practicas = $appraisal->practices
            ->filter(fn (Practice $practica): bool => $practica->level <= $nivelObjetivo)
            ->sortBy(fn (Practice $practica): string => $this->codigoArea($practica).'|'.$practica->code)
            ->values();

        $resultados = [];

        foreach ($practicas as $practica) {
            $evaluacion = $datos['evaluaciones']->get($practica->id);

            $resultados[] = $this->validarCriteriosCumplidos(
                $practica,
                $evaluacion instanceof PracticeEvaluation ? $evaluacion : null,
                (int) $datos['evidencias']->get($practica->id, 0),
            );
        }

        $score = $this->calcularScoreSimulado($resultados);
        $brechas = array_merge([], ...array_column($resultados, 'brechas'));

        if ($practicas->isEmpty()) {
            $brechas[] = $this->brecha('—', '—', '', self::TIPO_ALCANCE, "El alcance no tiene prácticas incluidas hasta el nivel {$nivelObjetivo}.", null, true);
        }

        $desglose = [
            'practicas_evaluadas' => count($resultados),
            'practicas_cumplen' => count(array_filter($resultados, fn (array $resultado): bool => $resultado['cumple'])),
            'brechas_bloqueantes' => count(array_filter($brechas, fn (array $brecha): bool => $brecha['bloqueante'])),
            'gaps_criticos' => count(array_filter($brechas, fn (array $brecha): bool => $brecha['severidad'] === Gap::SEVERITY_CRITICA)),
        ];

        $readiness = $this->determinarReadiness($score, $brechas, $practicas->isEmpty());

        return DB::transaction(function () use ($appraisal, $nivelObjetivo, $usuario, $score, $brechas, $desglose, $readiness): ResultadoSimulacionDTO {
            $simulacion = AppraisalSimulation::create([
                'appraisal_id' => $appraisal->id,
                'executed_by' => $usuario?->id,
                'target_level' => $nivelObjetivo,
                'score' => $score,
                'evaluated_practices' => $desglose['practicas_evaluadas'],
                'passed_practices' => $desglose['practicas_cumplen'],
                'gaps_found' => $brechas,
            ]);

            $simulacion->readinessMeasurement()->create([
                'appraisal_id' => $appraisal->id,
                'status' => $readiness,
                'level' => $nivelObjetivo,
                'score' => $score,
                'breakdown' => $desglose,
                'calculated_at' => now(),
            ]);

            return new ResultadoSimulacionDTO(
                simulacionId: $simulacion->id,
                score: $score,
                nivel: $nivelObjetivo,
                readiness: $readiness,
                brechas: $brechas,
                desglose: $desglose,
                generadoEn: $simulacion->created_at ?? now(),
            );
        });
    }

    /**
     * @return array{cumple: bool, brechas: list<array{area: string, practica: string, practica_nombre: string, tipo: string, detalle: string, severidad: string|null, bloqueante: bool}>}
     */
    public function validarCriteriosCumplidos(Practice $practica, ?PracticeEvaluation $evaluacion, int $evidenciasVerificadas): array
    {
        $area = $this->codigoArea($practica);
        $brechas = [];

        if (! $evaluacion instanceof PracticeEvaluation) {
            $brechas[] = $this->brecha($area, $practica->code, $practica->name, self::TIPO_EVALUACION, 'La práctica aún no ha sido evaluada.', null, true);
        } else {
            $chequeos = $evaluacion->criterionChecks->keyBy('practice_criterion_id');

            foreach ($practica->criteria as $criterio) {
                if (! $criterio->estado || ! $criterio->required) {
                    continue;
                }

                $chequeo = $chequeos->get($criterio->id);
                $estado = $chequeo instanceof CriterionCheck ? $chequeo->status : CriterionCheck::STATUS_PENDIENTE;

                if (! in_array($estado, [CriterionCheck::STATUS_CUMPLE, CriterionCheck::STATUS_NO_APLICA], true)) {
                    $brechas[] = $this->brecha($area, $practica->code, $practica->name, self::TIPO_CRITERIO, "{$criterio->code}: {$criterio->description} ({$estado}).", null, true);
                }
            }
        }

        if ($evidenciasVerificadas === 0) {
            $brechas[] = $this->brecha($area, $practica->code, $practica->name, self::TIPO_EVIDENCIA, 'No tiene evidencias verificadas del proyecto.', null, true);
        }

        if ($evaluacion instanceof PracticeEvaluation) {
            foreach ($evaluacion->gaps as $gap) {
                if (in_array($gap->status, [Gap::STATUS_VERIFICADO, Gap::STATUS_CERRADO], true)) {
                    continue;
                }

                $brechas[] = $this->brecha(
                    $area,
                    $practica->code,
                    $practica->name,
                    self::TIPO_GAP,
                    "{$gap->code}: {$gap->title} ({$gap->status}).",
                    $gap->severity,
                    in_array($gap->severity, self::SEVERIDADES_BLOQUEANTES, true),
                );
            }
        }

        return [
            'cumple' => array_filter($brechas, fn (array $brecha): bool => $brecha['bloqueante']) === [],
            'brechas' => $brechas,
        ];
    }

    /**
     * @param  list<array{cumple: bool, brechas: list<array<string, mixed>>}>  $resultados
     */
    public function calcularScoreSimulado(array $resultados): float
    {
        if ($resultados === []) {
            return 0.0;
        }

        $cumplen = count(array_filter($resultados, fn (array $resultado): bool => $resultado['cumple']));

        return round($cumplen / count($resultados) * 100, 2);
    }

    /**
     * @param  list<array{area: string, practica: string, practica_nombre: string, tipo: string, detalle: string, severidad: string|null, bloqueante: bool}>  $brechas
     */
    public function determinarReadiness(float $score, array $brechas, bool $sinAlcance = false): string
    {
        $gaps = array_filter($brechas, fn (array $brecha): bool => $brecha['tipo'] === self::TIPO_GAP);
        $hayGapCritico = array_filter($gaps, fn (array $brecha): bool => $brecha['severidad'] === Gap::SEVERITY_CRITICA) !== [];

        if ($sinAlcance || $hayGapCritico || $score < ReadinessMeasurement::UMBRAL_CON_CONDICIONES) {
            return ReadinessMeasurement::STATUS_NO_LISTO;
        }

        if ($score >= 100.0 && $gaps === []) {
            return ReadinessMeasurement::STATUS_LISTO;
        }

        return ReadinessMeasurement::STATUS_LISTO_CON_CONDICIONES;
    }

    private function codigoArea(Practice $practica): string
    {
        return $practica->practiceArea instanceof PracticeArea ? $practica->practiceArea->code : '—';
    }

    /**
     * @return array{area: string, practica: string, practica_nombre: string, tipo: string, detalle: string, severidad: string|null, bloqueante: bool}
     */
    private function brecha(string $area, string $practica, string $nombre, string $tipo, string $detalle, ?string $severidad, bool $bloqueante): array
    {
        return [
            'area' => $area,
            'practica' => $practica,
            'practica_nombre' => $nombre,
            'tipo' => $tipo,
            'detalle' => $detalle,
            'severidad' => $severidad,
            'bloqueante' => $bloqueante,
        ];
    }
}
