<?php

namespace Database\Seeders;

use App\Models\Appraisal;
use App\Models\AppraisalScope;
use App\Models\AppraisalSimulation;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\CorrectiveActionLog;
use App\Models\CriterionCheck;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\EvidenceVersion;
use App\Models\Gap;
use App\Models\GapLog;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\Rol;
use App\Models\User;
use App\Services\AppraisalSimulationService;
use App\Services\PracticeStatusCalculator;
use Database\Seeders\Demo\EscenarioInicial;
use Database\Seeders\Demo\EscenarioIntermedio;
use Database\Seeders\Demo\EscenarioPresentacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class EscenariosDemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $this->call([
            EscenarioPresentacion::class,
            EscenarioIntermedio::class,
            EscenarioInicial::class,
        ]);
    }

    public static function usuario(string $email, string $name, int $rol): User
    {
        $usuario = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $usuario->syncRoles([Rol::findById($rol)]);

        return $usuario;
    }

    /**
     * @param  array<int, User>  $integrantes
     */
    public static function proyecto(string $code, string $name, string $inicio, array $integrantes): Project
    {
        $proyecto = Project::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'start_date' => $inicio,
                'status' => 'activo',
            ]
        );

        $proyecto->users()->syncWithoutDetaching(collect($integrantes)->pluck('id')->all());

        return $proyecto;
    }

    /**
     * @param  array<int, int>  $niveles
     */
    public static function appraisal(Project $proyecto, string $name, int $nivel, string $fecha, string $estado, array $niveles): Appraisal
    {
        $appraisal = Appraisal::updateOrCreate(
            ['project_id' => $proyecto->id, 'name' => $name],
            [
                'domain' => 'Development',
                'target_level' => $nivel,
                'target_date' => $fecha,
                'status' => $estado,
            ]
        );

        $practicas = Practice::whereIn('level', $niveles)->pluck('id');
        $appraisal->practices()->syncWithoutDetaching(AppraisalScope::pivotFor($practicas));

        return $appraisal;
    }

    /**
     * @param  array<int, string>  $estados
     */
    public static function evaluar(Appraisal $appraisal, string $codigo, array $estados, User $evaluador, string $fecha): PracticeEvaluation
    {
        $practica = Practice::where('code', $codigo)->firstOrFail();

        $evaluacion = PracticeEvaluation::firstOrCreate(
            ['appraisal_id' => $appraisal->id, 'practice_id' => $practica->id],
            ['status' => PracticeEvaluation::STATUS_NO_EVALUADA]
        );

        foreach ($practica->criteria->values() as $posicion => $criterio) {
            $estado = $estados[$posicion] ?? CriterionCheck::STATUS_PENDIENTE;

            if ($estado === CriterionCheck::STATUS_PENDIENTE) {
                continue;
            }

            CriterionCheck::firstOrCreate(
                ['practice_evaluation_id' => $evaluacion->id, 'practice_criterion_id' => $criterio->id],
                [
                    'status' => $estado,
                    'evaluated_by' => $evaluador->id,
                    'evaluated_at' => $fecha,
                ]
            );
        }

        return $evaluacion;
    }

    /**
     * @param  array<int, string>  $practicas
     */
    public static function evidencia(Project $proyecto, string $nombre, string $tipo, int $estado, User $subio, array $practicas, string $fecha, ?User $verificador = null, ?string $motivo = null, int $versiones = 1): Evidence
    {
        $evidencia = Evidence::firstOrCreate(
            ['project_id' => $proyecto->id, 'name' => $nombre],
            [
                'code' => Evidence::generateNextCode(),
                'type' => $tipo,
                'description' => 'Producto de trabajo del proyecto '.$proyecto->name.'.',
                'status_id' => $estado,
                'uploaded_by' => $subio->id,
                'verification_reason' => $motivo,
                'verified_by' => $verificador?->id,
                'verified_at' => $verificador instanceof User ? $fecha : null,
            ]
        );

        for ($numero = 1; $numero <= $versiones; $numero++) {
            EvidenceVersion::firstOrCreate(
                ['evidence_id' => $evidencia->id, 'number' => $numero],
                [
                    'file_public_id' => 'demo/evidencias/'.$evidencia->code.'-v'.$numero,
                    'file_url' => 'https://demo.dima.bo/evidencias/'.$evidencia->code.'-v'.$numero.'.pdf',
                    'file_resource_type' => 'raw',
                    'file_format' => 'pdf',
                    'file_original_name' => $nombre.' v'.$numero.'.pdf',
                    'file_size' => 250000 + ($numero * 48000),
                    'uploaded_by' => $subio->id,
                    'uploaded_at' => Carbon::parse($fecha)->subDays($versiones - $numero),
                ]
            );
        }

        $ids = Practice::whereIn('code', $practicas)->pluck('id');
        $evidencia->practices()->syncWithoutDetaching($ids->all());

        return $evidencia;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function gap(PracticeEvaluation $evaluacion, array $datos): Gap
    {
        $gap = Gap::firstOrCreate(
            ['practice_evaluation_id' => $evaluacion->id, 'title' => $datos['title']],
            array_merge(
                [
                    'code' => Gap::generateNextCode(),
                    'status' => Gap::STATUS_ABIERTO,
                    'severity' => Gap::SEVERITY_MEDIA,
                ],
                collect($datos)->except(['title', 'bitacora'])->all()
            )
        );

        foreach ($datos['bitacora'] ?? [] as $registro) {
            GapLog::firstOrCreate(
                ['gap_id' => $gap->id, 'field' => $registro['field'], 'new_value' => $registro['new_value']],
                [
                    'user_id' => $registro['user_id'],
                    'old_value' => $registro['old_value'] ?? null,
                    'description' => $registro['description'],
                ]
            );
        }

        return $gap;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function accion(Gap $gap, array $datos): CorrectiveAction
    {
        $accion = CorrectiveAction::firstOrCreate(
            ['gap_id' => $gap->id, 'description' => $datos['description']],
            collect($datos)->except(['description', 'bitacora'])->all()
        );

        foreach ($datos['bitacora'] ?? [] as $registro) {
            CorrectiveActionLog::firstOrCreate(
                ['corrective_action_id' => $accion->id, 'field' => $registro['field'], 'new_value' => $registro['new_value']],
                [
                    'user_id' => $registro['user_id'],
                    'old_value' => $registro['old_value'] ?? null,
                    'description' => $registro['description'],
                ]
            );
        }

        return $accion;
    }

    public static function recalcularEstados(Appraisal $appraisal): void
    {
        foreach ($appraisal->practiceEvaluations()->get() as $evaluacion) {
            PracticeStatusCalculator::calculateForEvaluation($evaluacion);
        }
    }

    public static function simular(Appraisal $appraisal, int $nivel, User $gestor): void
    {
        if ($appraisal->simulations()->whereDate('created_at', now()->toDateString())->exists()) {
            return;
        }

        app(AppraisalSimulationService::class)->ejecutarSimulacion($appraisal->id, $nivel, $gestor);
    }

    public static function simulacionHistorica(Appraisal $appraisal, int $nivel, User $gestor, float $score, string $estado, int $evaluadas, int $cumplen, string $fecha): void
    {
        $existente = AppraisalSimulation::where('appraisal_id', $appraisal->id)
            ->where('created_at', Carbon::parse($fecha))
            ->exists();

        if ($existente) {
            return;
        }

        $simulacion = AppraisalSimulation::create([
            'appraisal_id' => $appraisal->id,
            'executed_by' => $gestor->id,
            'target_level' => $nivel,
            'score' => $score,
            'evaluated_practices' => $evaluadas,
            'passed_practices' => $cumplen,
            'gaps_found' => [],
        ]);

        $desglose = [
            'practicas_evaluadas' => $evaluadas,
            'practicas_cumplen' => $cumplen,
            'brechas_bloqueantes' => $evaluadas - $cumplen,
            'gaps_criticos' => 0,
        ];

        $simulacion->readinessMeasurement()->create([
            'appraisal_id' => $appraisal->id,
            'status' => $estado,
            'level' => $nivel,
            'score' => $score,
            'breakdown' => $desglose,
            'calculated_at' => $fecha,
        ]);

        $simulacion->forceFill(['created_at' => $fecha, 'updated_at' => $fecha])->save();
    }

    /**
     * @param  array<string, mixed>|null  $anteriores
     * @param  array<string, mixed>|null  $nuevos
     */
    public static function bitacora(User $usuario, string $accion, Model $modelo, string $fecha, ?array $anteriores = null, ?array $nuevos = null): void
    {
        $registro = AuditLog::firstOrCreate(
            [
                'auditable_type' => $modelo::class,
                'auditable_id' => $modelo->getKey(),
                'action' => $accion,
                'entity' => AuditLog::describe($modelo),
            ],
            [
                'user_id' => $usuario->id,
                'old_values' => $anteriores,
                'new_values' => $nuevos,
                'ip_address' => '10.20.30.40',
            ]
        );

        $registro->forceFill(['created_at' => $fecha])->save();
    }

    public static function estadoEvidencia(string $nombre): int
    {
        return match ($nombre) {
            'verificada' => EvidenceStatus::VERIFICADA,
            'observada' => EvidenceStatus::OBSERVADA,
            'rechazada' => EvidenceStatus::RECHAZADA,
            default => EvidenceStatus::REGISTRADA,
        };
    }
}
