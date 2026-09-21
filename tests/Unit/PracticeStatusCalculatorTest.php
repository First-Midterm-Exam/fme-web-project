<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PracticeStatusCalculator;
use PHPUnit\Framework\TestCase;

final class PracticeStatusCalculatorTest extends TestCase
{
    public function test_retorna_no_evaluada_cuando_ningun_criterio_ha_sido_evaluado(): void
    {
        $chequeos = [
            ['evaluado' => false, 'cumplido' => false, 'obligatorio' => true],
            ['evaluado' => false, 'cumplido' => false, 'obligatorio' => false],
        ];

        $resultado = PracticeStatusCalculator::calculate($chequeos);

        $this->assertSame(PracticeStatusCalculator::NO_EVALUADA, $resultado);
    }

    public function test_retorna_no_cumple_cuando_se_evaluan_criterios_pero_ninguno_se_cumple(): void
    {
        $chequeos = [
            ['evaluado' => true, 'cumplido' => false, 'obligatorio' => true],
            ['evaluado' => true, 'cumplido' => false, 'obligatorio' => false],
        ];

        $resultado = PracticeStatusCalculator::calculate($chequeos);

        $this->assertSame(PracticeStatusCalculator::NO_CUMPLE, $resultado);
    }

    public function test_retorna_parcial_cuando_se_cumplen_algunos_pero_falta_algun_obligatorio(): void
    {
        $chequeos = [
            ['evaluado' => true, 'cumplido' => true, 'obligatorio' => false],
            ['evaluado' => true, 'cumplido' => false, 'obligatorio' => true],
        ];

        $resultado = PracticeStatusCalculator::calculate($chequeos);

        $this->assertSame(PracticeStatusCalculator::PARCIAL, $resultado);
    }

    public function test_retorna_cumple_cuando_todos_los_criterios_obligatorios_estan_cumplidos(): void
    {
        $chequeos = [
            ['evaluado' => true, 'cumplido' => true, 'obligatorio' => true],
            ['evaluado' => true, 'cumplido' => true, 'obligatorio' => true],
            ['evaluado' => true, 'cumplido' => false, 'obligatorio' => false],
        ];

        $resultado = PracticeStatusCalculator::calculate($chequeos);

        $this->assertSame(PracticeStatusCalculator::CUMPLE, $resultado);
    }

    public function test_retorna_verificada_cuando_cumple_obligatorios_y_todas_las_evidencias_estan_verificadas(): void
    {
        $chequeos = [
            ['evaluado' => true, 'cumplido' => true, 'obligatorio' => true],
        ];

        $evidencias = [
            ['verificada' => true],
            ['verificada' => true],
        ];

        $resultado = PracticeStatusCalculator::calculate($chequeos, $evidencias);

        $this->assertSame(PracticeStatusCalculator::VERIFICADA, $resultado);
    }
}
