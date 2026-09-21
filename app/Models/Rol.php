<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class Rol extends Role
{
    public const ADMINISTRADOR = 1;

    public const GESTOR_PROCESOS = 2;

    public const JEFE_PROYECTO = 3;

    public const COLABORADOR = 4;

    public const ETIQUETAS = [
        self::ADMINISTRADOR => 'Administrador',
        self::GESTOR_PROCESOS => 'Gestor de Procesos',
        self::JEFE_PROYECTO => 'Jefe de Proyecto',
        self::COLABORADOR => 'Colaborador',
    ];

    public const SLUGS = [
        self::ADMINISTRADOR => 'administrador',
        self::GESTOR_PROCESOS => 'gestor_procesos',
        self::JEFE_PROYECTO => 'jefe_proyecto',
        self::COLABORADOR => 'colaborador',
    ];

    public function usuarios(): BelongsToMany
    {
        return $this->users();
    }

    public function etiqueta(): string
    {
        return self::ETIQUETAS[$this->id] ?? $this->name;
    }

    public function slug(): string
    {
        return self::SLUGS[$this->id] ?? Str::slug($this->name, '_');
    }

    public function color(): string
    {
        return match ($this->id) {
            self::ADMINISTRADOR => 'bg-danger',
            self::GESTOR_PROCESOS => 'bg-primary',
            self::JEFE_PROYECTO => 'bg-info text-dark',
            default => 'bg-secondary',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function opciones(): array
    {
        return self::query()->orderBy('id')->get()
            ->mapWithKeys(fn (self $rol): array => [$rol->id => $rol->etiqueta()])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public static function mapaDeSlugs(): array
    {
        return self::query()->orderBy('id')->get()
            ->mapWithKeys(fn (self $rol): array => [$rol->slug() => $rol->id])
            ->all();
    }
}
