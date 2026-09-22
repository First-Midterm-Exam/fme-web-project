<?php

namespace App\Services\Asistente;

use App\Models\Appraisal;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Services\Asistente\Fuentes\FuenteGaps;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecuperadorContexto
{
    public function __construct(private readonly FuenteGaps $gaps) {}

    public function recuperar(string $tipo, Appraisal $appraisal): ContextoReporte
    {
        $appraisal->loadMissing('project');

        return match ($tipo) {
            CatalogoReportes::CUMPLIMIENTO => $this->cumplimiento($appraisal),
            CatalogoReportes::CRITERIOS => $this->criterios($appraisal),
            CatalogoReportes::EVIDENCIAS => $this->evidencias($appraisal),
            CatalogoReportes::GAPS => $this->gapsCriticos($appraisal),
            CatalogoReportes::GENERAL => $this->resumen($appraisal, CatalogoReportes::GENERAL),
            default => $this->resumen($appraisal, CatalogoReportes::RESUMEN),
        };
    }

    private function resumen(Appraisal $appraisal, string $tipo): ContextoReporte
    {
        $practicas = $this->practicasEvaluadas($appraisal);

        $porEstado = $practicas
            ->countBy(fn (array $item): string => $item['evaluacion']->status ?? PracticeEvaluation::STATUS_NO_EVALUADA)
            ->sortKeys();

        $promedio = $practicas->isEmpty()
            ? 0
            : (int) round($practicas->avg(fn (array $item): int => $item['evaluacion']?->compliancePercentage() ?? 0));

        $evidencias = Evidence::where('project_id', $appraisal->project_id)->count();

        $hechos = [
            'Appraisal' => $appraisal->name,
            'Proyecto' => $appraisal->project->name,
            'Dominio' => $appraisal->domain,
            'Nivel objetivo' => $appraisal->target_level,
            'Fecha meta' => Carbon::parse($appraisal->target_date)->format('Y-m-d'),
            'Estado del appraisal' => ucfirst($appraisal->status),
            'Prácticas en el alcance' => $practicas->count(),
            'Áreas de práctica en el alcance' => $practicas->pluck('practica.practice_area_id')->unique()->count(),
            'Cumplimiento promedio de criterios' => $promedio.'%',
            'Evidencias registradas en el proyecto' => $evidencias,
        ];

        foreach ($porEstado as $estado => $cantidad) {
            $hechos['Prácticas en estado '.$estado] = $cantidad;
        }

        $filas = [];
        foreach ($hechos as $indicador => $valor) {
            $filas[] = [$indicador, $valor];
        }

        return new ContextoReporte($tipo, CatalogoReportes::titulo($tipo), ['Indicador', 'Valor'], $filas, $hechos);
    }

    private function cumplimiento(Appraisal $appraisal): ContextoReporte
    {
        $practicas = $this->practicasEvaluadas($appraisal);

        $filas = $practicas->map(fn (array $item): array => [
            $item['practica']->code,
            $item['practica']->name,
            $item['practica']->practiceArea->code,
            $item['practica']->level,
            $item['evaluacion']->status ?? PracticeEvaluation::STATUS_NO_EVALUADA,
            ($item['evaluacion']?->compliancePercentage() ?? 0).'%',
        ])->values()->all();

        $completas = $practicas->filter(fn (array $item): bool => ($item['evaluacion']?->compliancePercentage() ?? 0) === 100)->count();

        return new ContextoReporte(
            CatalogoReportes::CUMPLIMIENTO,
            CatalogoReportes::titulo(CatalogoReportes::CUMPLIMIENTO),
            ['Código', 'Práctica', 'Área', 'Nivel', 'Estado', 'Cumplimiento'],
            $filas,
            [
                'Prácticas en el alcance' => $practicas->count(),
                'Prácticas con 100% de criterios cumplidos' => $completas,
            ],
        );
    }

    private function criterios(Appraisal $appraisal): ContextoReporte
    {
        $filas = [];
        $noCumplidos = 0;
        $pendientes = 0;

        foreach ($this->practicasEvaluadas($appraisal) as $item) {
            $chequeos = $item['evaluacion']?->criterionChecks->keyBy('practice_criterion_id') ?? collect();

            foreach ($item['practica']->criteria->where('estado', true) as $criterio) {
                $chequeo = $chequeos->get($criterio->id);
                $estado = $chequeo instanceof CriterionCheck ? $chequeo->status : CriterionCheck::STATUS_PENDIENTE;

                if (! in_array($estado, [CriterionCheck::STATUS_NO_CUMPLE, CriterionCheck::STATUS_PENDIENTE], true)) {
                    continue;
                }

                $estado === CriterionCheck::STATUS_NO_CUMPLE ? $noCumplidos++ : $pendientes++;

                $filas[] = [
                    $item['practica']->code,
                    $criterio->code,
                    $criterio->description,
                    $criterio->required ? 'Sí' : 'No',
                    $estado,
                    $chequeo instanceof CriterionCheck ? $chequeo->notes : null,
                ];
            }
        }

        return new ContextoReporte(
            CatalogoReportes::CRITERIOS,
            CatalogoReportes::titulo(CatalogoReportes::CRITERIOS),
            ['Práctica', 'Criterio', 'Descripción', 'Obligatorio', 'Estado', 'Observación'],
            $filas,
            [
                'Criterios no cumplidos' => $noCumplidos,
                'Criterios pendientes de evaluar' => $pendientes,
            ],
        );
    }

    private function evidencias(Appraisal $appraisal): ContextoReporte
    {
        $evidencias = Evidence::query()
            ->with(['status', 'currentVersion', 'uploadedBy'])
            ->where('project_id', $appraisal->project_id)
            ->orderBy('code')
            ->get();

        $filas = $evidencias->map(fn (Evidence $evidencia): array => [
            $evidencia->code,
            $evidencia->name,
            Evidence::TYPES[$evidencia->type] ?? $evidencia->type,
            $evidencia->status->name,
            $evidencia->currentVersion?->number,
            $evidencia->uploadedBy->name,
            $evidencia->created_at?->format('Y-m-d'),
        ])->values()->all();

        $hechos = ['Evidencias registradas' => $evidencias->count()];

        foreach ($evidencias->countBy(fn (Evidence $evidencia): string => Evidence::TYPES[$evidencia->type] ?? $evidencia->type) as $tipo => $cantidad) {
            $hechos['Evidencias de tipo '.$tipo] = $cantidad;
        }

        return new ContextoReporte(
            CatalogoReportes::EVIDENCIAS,
            CatalogoReportes::titulo(CatalogoReportes::EVIDENCIAS),
            ['Código', 'Nombre', 'Tipo', 'Estado', 'Versión', 'Cargada por', 'Fecha'],
            $filas,
            $hechos,
        );
    }

    private function gapsCriticos(Appraisal $appraisal): ContextoReporte
    {
        $gaps = collect($this->gaps->gapsDe($appraisal));
        $criticos = $gaps->where('severidad', 'alta');
        $sinClasificar = $criticos->isEmpty() && $gaps->whereNull('severidad')->isNotEmpty();

        $filas = ($sinClasificar ? $gaps : $criticos)->sortBy('fecha_limite')->values()->map(fn (array $gap): array => [
            $gap['codigo'],
            $gap['titulo'],
            $gap['practica'],
            $gap['severidad'] === null ? 'Sin asignar' : ucfirst($gap['severidad']),
            $gap['estado'],
            $gap['fecha_limite'],
        ])->all();

        return new ContextoReporte(
            CatalogoReportes::GAPS,
            CatalogoReportes::titulo(CatalogoReportes::GAPS),
            ['Código', 'Gap', 'Práctica', 'Severidad', 'Estado', 'Fecha límite'],
            $filas,
            [
                'Gaps registrados' => $gaps->count(),
                'Gaps críticos' => $criticos->count(),
            ],
            $sinClasificar ? 'Los gaps aún no tienen severidad asignada; se muestran todos los gaps abiertos.' : null,
        );
    }

    /**
     * @return Collection<int, array{practica: Practice, evaluacion: PracticeEvaluation|null}>
     */
    private function practicasEvaluadas(Appraisal $appraisal): Collection
    {
        return $appraisal->practices()
            ->with([
                'practiceArea',
                'criteria',
                'evaluations' => fn ($query) => $query->where('appraisal_id', $appraisal->id),
                'evaluations.criterionChecks',
            ])
            ->orderBy('practices.code')
            ->get()
            ->map(function (Practice $practica): array {
                $evaluacion = $practica->evaluations->first();

                if ($evaluacion instanceof PracticeEvaluation) {
                    $evaluacion->setRelation('practice', $practica);
                }

                return [
                    'practica' => $practica,
                    'evaluacion' => $evaluacion instanceof PracticeEvaluation ? $evaluacion : null,
                ];
            })
            ->values();
    }
}
