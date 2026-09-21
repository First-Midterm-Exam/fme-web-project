<?php

namespace App\Services\Asistente;

final class ContextoReporte
{
    /**
     * @param  list<string>  $columnas
     * @param  list<list<string|int|float|null>>  $filas
     * @param  array<string, string|int|float|null>  $hechos
     */
    public function __construct(
        public readonly string $tipo,
        public readonly string $titulo,
        public readonly array $columnas,
        public readonly array $filas,
        public readonly array $hechos,
        public readonly ?string $nota = null,
    ) {}

    public function tablaMarkdown(): string
    {
        if ($this->filas === []) {
            return '_Sin registros para este reporte._';
        }

        $celda = fn (string|int|float|null $valor): string => str_replace(['|', "\n"], ['\|', ' '], (string) ($valor ?? '—'));

        $lineas = [
            '| '.implode(' | ', array_map($celda, $this->columnas)).' |',
            '|'.str_repeat(' --- |', count($this->columnas)),
        ];

        foreach ($this->filas as $fila) {
            $lineas[] = '| '.implode(' | ', array_map($celda, $fila)).' |';
        }

        return implode("\n", $lineas);
    }
}
