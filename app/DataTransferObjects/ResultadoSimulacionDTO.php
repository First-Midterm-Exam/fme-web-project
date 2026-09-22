<?php

namespace App\DataTransferObjects;

use App\Models\AppraisalSimulation;
use App\Models\ReadinessMeasurement;
use Carbon\CarbonInterface;

final class ResultadoSimulacionDTO
{
    /**
     * @param  list<array{area: string, practica: string, practica_nombre: string, tipo: string, detalle: string, severidad: string|null, bloqueante: bool}>  $brechas
     * @param  array{practicas_evaluadas: int, practicas_cumplen: int, brechas_bloqueantes: int, gaps_criticos: int}  $desglose
     */
    public function __construct(
        public readonly int $simulacionId,
        public readonly float $score,
        public readonly int $nivel,
        public readonly string $readiness,
        public readonly array $brechas,
        public readonly array $desglose,
        public readonly CarbonInterface $generadoEn,
    ) {}

    public static function desdeSimulacion(AppraisalSimulation $simulacion): self
    {
        $medicion = $simulacion->readinessMeasurement;

        return new self(
            simulacionId: $simulacion->id,
            score: $simulacion->score,
            nivel: $simulacion->target_level,
            readiness: $medicion instanceof ReadinessMeasurement ? $medicion->status : ReadinessMeasurement::STATUS_NO_LISTO,
            brechas: $simulacion->gaps_found ?? [],
            desglose: $medicion instanceof ReadinessMeasurement ? $medicion->breakdown : [
                'practicas_evaluadas' => $simulacion->evaluated_practices,
                'practicas_cumplen' => $simulacion->passed_practices,
                'brechas_bloqueantes' => 0,
                'gaps_criticos' => 0,
            ],
            generadoEn: $simulacion->created_at ?? now(),
        );
    }

    /**
     * @return array{simulacion_id: int, score: float, nivel: int, readiness: string, brechas: list<array{area: string, practica: string, practica_nombre: string, tipo: string, detalle: string, severidad: string|null, bloqueante: bool}>, desglose: array{practicas_evaluadas: int, practicas_cumplen: int, brechas_bloqueantes: int, gaps_criticos: int}, generado_en: string}
     */
    public function toArray(): array
    {
        return [
            'simulacion_id' => $this->simulacionId,
            'score' => $this->score,
            'nivel' => $this->nivel,
            'readiness' => $this->readiness,
            'brechas' => $this->brechas,
            'desglose' => $this->desglose,
            'generado_en' => $this->generadoEn->format('d/m/Y H:i'),
        ];
    }
}
