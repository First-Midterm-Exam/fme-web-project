<?php

use App\Models\Rol;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ANTERIORES = [
        Rol::ADMINISTRADOR => 'Administrador',
        Rol::GESTOR_PROCESOS => 'Process Manager / CMMI Manager',
        Rol::JEFE_PROYECTO => 'Project Manager',
        Rol::COLABORADOR => 'Contributor',
    ];

    public function up(): void
    {
        foreach (Rol::ETIQUETAS as $id => $nombre) {
            DB::table('roles')->where('id', $id)->update(['name' => $nombre]);
        }
    }

    public function down(): void
    {
        foreach (self::ANTERIORES as $id => $nombre) {
            DB::table('roles')->where('id', $id)->update(['name' => $nombre]);
        }
    }
};
