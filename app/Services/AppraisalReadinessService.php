<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appraisal;
use Illuminate\Support\Facades\DB;

class AppraisalReadinessService
{
    public const WEIGHT_PRACTICES = 0.40; // 40%
    public const WEIGHT_EVIDENCES = 0.40; // 40%
    public const WEIGHT_GAPS = 0.20;      // 20%

    public const DISCLAIMER_TEXT = 'El Appraisal Readiness Score es una métrica interna de diagnóstico y preparación operativa del proyecto. No constituye, bajo ninguna circunstancia, una garantía, predicción o probabilidad de certificación oficial CMMI emitida por evaluadores acreditados.';

    /**
     * Calcula el puntaje de preparación y su desglose ponderado.
     *
     * @return array{
     *     score: float,
     *     disclaimer: string,
     *     breakdown: array{
     *         practices: array{count: int, total: int, completion_rate: float, weight: float, contribution: float},
     *         evidences: array{count: int, required: int, completion_rate: float, weight: float, contribution: float},
     *         gaps: array{open_count: int, total_count: int, health_rate: float, weight: float, contribution: float}
     *     }
     * }
     */
    public function calculate(Appraisal $appraisal): array
    {
        // 1. Componente: Prácticas
        $totalPractices = $appraisal->practices()->count();
        $evaluatedPractices = $appraisal->practiceEvaluations()
            ->whereIn('status', ['Cumplida', 'Evaluada', 'Completed', 'evaluated', 'Conforme'])
            ->count();

        $practiceRate = $totalPractices > 0 ? min(1.0, $evaluatedPractices / $totalPractices) : 0.0;
        $practiceContribution = round($practiceRate * self::WEIGHT_PRACTICES * 100, 2);

        // 2. Componente: Evidencias Verificadas del Proyecto
        $projectId = $appraisal->project_id;
        $totalRequiredEvidences = max($totalPractices, 1);

        $verifiedEvidences = DB::table('evidences')
            ->join('evidence_statuses', 'evidences.status_id', '=', 'evidence_statuses.id')
            ->where('evidences.project_id', $projectId)
            ->whereIn('evidence_statuses.name', ['Verificada', 'Aprobada', 'verified', 'approved'])
            ->count();

        $evidenceRate = min(1.0, $verifiedEvidences / $totalRequiredEvidences);
        $evidenceContribution = round($evidenceRate * self::WEIGHT_EVIDENCES * 100, 2);

        // 3. Componente: Gaps Abiertos (Penalización sobre el 20%)
        $evaluationIds = $appraisal->practiceEvaluations()->pluck('id');

        $totalGaps = DB::table('gaps')
            ->whereIn('practice_evaluation_id', $evaluationIds)
            ->count();

        $openGaps = DB::table('gaps')
            ->whereIn('practice_evaluation_id', $evaluationIds)
            ->whereIn('status', ['Abierto', 'Pendiente', 'open', 'pending'])
            ->count();

        $gapHealthRate = $totalGaps === 0 ? 1.0 : max(0.0, 1.0 - ($openGaps / $totalGaps));
        $gapContribution = round($gapHealthRate * self::WEIGHT_GAPS * 100, 2);

        // Score Global Ponderado (acotado de 0 a 100%)
        $totalScore = round(min(100.0, max(0.0, $practiceContribution + $evidenceContribution + $gapContribution)), 1);

        return [
            'score' => $totalScore,
            'disclaimer' => self::DISCLAIMER_TEXT,
            'breakdown' => [
                'practices' => [
                    'count' => $evaluatedPractices,
                    'total' => $totalPractices,
                    'completion_rate' => round($practiceRate * 100, 1),
                    'weight' => self::WEIGHT_PRACTICES * 100,
                    'contribution' => $practiceContribution,
                ],
                'evidences' => [
                    'count' => $verifiedEvidences,
                    'required' => $totalRequiredEvidences,
                    'completion_rate' => round($evidenceRate * 100, 1),
                    'weight' => self::WEIGHT_EVIDENCES * 100,
                    'contribution' => $evidenceContribution,
                ],
                'gaps' => [
                    'open_count' => $openGaps,
                    'total_count' => $totalGaps,
                    'health_rate' => round($gapHealthRate * 100, 1),
                    'weight' => self::WEIGHT_GAPS * 100,
                    'contribution' => $gapContribution,
                ],
            ],
        ];
    }
}