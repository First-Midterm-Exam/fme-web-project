<?php

namespace App\Support;

use App\Models\Rol;

final class Capacidades
{
    public const GESTIONAR_USUARIOS = 'gestionar-usuarios';

    public const VER_PROYECTOS = 'ver-proyectos';

    public const ADMINISTRAR_PROYECTOS = 'administrar-proyectos';

    public const VER_APPRAISALS = 'ver-appraisals';

    public const CONFIGURAR_APPRAISAL = 'configurar-appraisal';

    public const DEFINIR_ALCANCE = 'definir-alcance';

    public const EVALUAR_CUMPLIMIENTO = 'evaluar-cumplimiento';

    public const VERIFICAR_EVIDENCIAS = 'verificar-evidencias';

    public const REGISTRAR_EVIDENCIA = 'registrar-evidencia';

    public const GESTIONAR_GAPS = 'gestionar-gaps';

    public const VER_READINESS = 'ver-readiness';

    public const MAPA = [
        self::GESTIONAR_USUARIOS => [Rol::ADMINISTRADOR],
        self::VER_PROYECTOS => [Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO, Rol::COLABORADOR],
        self::ADMINISTRAR_PROYECTOS => [Rol::ADMINISTRADOR],
        self::VER_APPRAISALS => [Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO, Rol::COLABORADOR],
        self::CONFIGURAR_APPRAISAL => [Rol::ADMINISTRADOR],
        self::DEFINIR_ALCANCE => [Rol::GESTOR_PROCESOS],
        self::EVALUAR_CUMPLIMIENTO => [Rol::GESTOR_PROCESOS],
        self::VERIFICAR_EVIDENCIAS => [Rol::GESTOR_PROCESOS, Rol::ADMINISTRADOR],
        self::REGISTRAR_EVIDENCIA => [Rol::JEFE_PROYECTO, Rol::COLABORADOR],
        self::GESTIONAR_GAPS => [Rol::GESTOR_PROCESOS],
        self::VER_READINESS => [Rol::GESTOR_PROCESOS, Rol::JEFE_PROYECTO],
    ];

    /**
     * @return array<int, string>
     */
    public static function todas(): array
    {
        return array_keys(self::MAPA);
    }

    /**
     * @return array<int, int>
     */
    public static function rolesDe(string $capacidad): array
    {
        return self::MAPA[$capacidad] ?? [];
    }
}
