<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\PracticeEvaluation;

final class PracticeStatusCalculator
{
    public const NO_EVALUADA = 'No evaluada';

    public const NO_CUMPLE = 'No cumple';

    public const PARCIAL = 'Parcial';

    public const CUMPLE = 'Cumple';

    public const VERIFICADA = 'Verificada';

    /**
     * @param  iterable<int, array<string, mixed>>  $chequeos
     * @param  iterable<int, array<string, mixed>>  $evidencias
     */
    public static function calculate(iterable $chequeos, iterable $evidencias = []): string
    {
        $chequeosArray = is_array($chequeos) ? $chequeos : iterator_to_array($chequeos);
        $evidenciasArray = is_array($evidencias) ? $evidencias : iterator_to_array($evidencias);

        $evaluados = array_filter($chequeosArray, static fn (array $item): bool => ! empty($item['evaluado']));

        if (count($evaluados) === 0) {
            return self::NO_EVALUADA;
        }

        $cumplidos = array_filter($evaluados, static fn (array $item): bool => ! empty($item['cumplido']));

        if (count($cumplidos) === 0) {
            return self::NO_CUMPLE;
        }

        $obligatorios = array_filter($chequeosArray, static fn (array $item): bool => ! empty($item['obligatorio']));
        $obligatoriosCumplidos = array_filter($obligatorios, static fn (array $item): bool => ! empty($item['cumplido']));

        $cumpleObligatorios = count($obligatorios) > 0 && (count($obligatoriosCumplidos) === count($obligatorios));

        if (! $cumpleObligatorios) {
            return self::PARCIAL;
        }

        if (! empty($evidenciasArray)) {
            $todasVerificadas = true;
            foreach ($evidenciasArray as $evidencia) {
                $verificada = ! empty($evidencia['verificada'])
                    || (($evidencia['resultadoVerificacion'] ?? '') === 'Aprobada')
                    || (($evidencia['estado'] ?? '') === 'Verificada');

                if (! $verificada) {
                    $todasVerificadas = false;
                    break;
                }
            }

            if ($todasVerificadas) {
                return self::VERIFICADA;
            }
        }

        return self::CUMPLE;
    }

    public static function calculateForEvaluation(PracticeEvaluation $evaluation): string
    {
        $evaluation->load(['criterionChecks', 'practice.criteria', 'appraisal']);

        $chequeos = $evaluation->criterionChecks->keyBy('practice_criterion_id');

        $criteriosData = [];
        foreach ($evaluation->practice->criteria->where('estado', true) as $criterion) {
            $check = $chequeos->get($criterion->id);
            $status = $check instanceof CriterionCheck ? $check->status : CriterionCheck::STATUS_PENDIENTE;

            if ($status === CriterionCheck::STATUS_NO_APLICA) {
                continue;
            }

            $criteriosData[] = [
                'cumplido' => $status === CriterionCheck::STATUS_CUMPLE,
                'obligatorio' => (bool) $criterion->required,
                'evaluado' => $status !== CriterionCheck::STATUS_PENDIENTE,
            ];
        }

        $evidenciasData = $evaluation->practice->evidences()
            ->where('evidences.project_id', $evaluation->appraisal->project_id)
            ->with('status')
            ->get()
            ->map(fn (Evidence $evidence): array => ['estado' => $evidence->status->name])
            ->values()
            ->all();

        $nuevoEstado = self::calculate($criteriosData, $evidenciasData);
        $evaluation->update(['status' => $nuevoEstado]);

        return $nuevoEstado;
    }
}
