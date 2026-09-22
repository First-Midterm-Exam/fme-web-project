<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_CREATED = 'Creación';

    public const ACTION_UPDATED = 'Actualización';

    public const ACTION_DELETED = 'Eliminación';

    public const ACTION_ROLE_CHANGED = 'Cambio de rol';

    public const ACTION_MEMBER_ADDED = 'Integrante agregado';

    public const ACTION_MEMBER_REMOVED = 'Integrante retirado';

    public const AUDITED_MODELS = [
        User::class => 'Usuario',
        Project::class => 'Proyecto',
        Appraisal::class => 'Appraisal',
        PracticeCriterion::class => 'Criterio de práctica',
        PracticeEvaluation::class => 'Evaluación de práctica',
        CriterionCheck::class => 'Chequeo de criterio',
        Evidence::class => 'Evidencia',
        EvidenceVersion::class => 'Versión de evidencia',
        Gap::class => 'Gap',
        CorrectiveAction::class => 'Acción correctiva',
    ];

    public const IGNORED_ATTRIBUTES = ['password', 'remember_token', 'created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'entity',
        'old_values',
        'new_values',
        'ip_address',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return list<string>
     */
    public static function actions(): array
    {
        return [
            self::ACTION_CREATED,
            self::ACTION_UPDATED,
            self::ACTION_DELETED,
            self::ACTION_ROLE_CHANGED,
            self::ACTION_MEMBER_ADDED,
            self::ACTION_MEMBER_REMOVED,
        ];
    }

    public static function describe(Model $model): string
    {
        $label = self::AUDITED_MODELS[$model::class] ?? class_basename($model);

        $identifier = $model->getAttribute('code')
            ?? $model->getAttribute('email')
            ?? $model->getAttribute('name')
            ?? ($model->getKey() !== null ? '#'.$model->getKey() : null);

        return trim($label.' '.$identifier);
    }

    public function entityType(): string
    {
        return self::AUDITED_MODELS[$this->auditable_type] ?? class_basename($this->auditable_type);
    }

    public function actionBadgeColor(): string
    {
        return match ($this->action) {
            self::ACTION_CREATED, self::ACTION_MEMBER_ADDED => 'bg-success',
            self::ACTION_DELETED, self::ACTION_MEMBER_REMOVED => 'bg-danger',
            self::ACTION_ROLE_CHANGED => 'bg-warning text-dark',
            default => 'bg-primary',
        };
    }

    /**
     * @return list<array{field: string, old: string, new: string}>
     */
    public function changes(): array
    {
        $old = $this->old_values ?? [];
        $new = $this->new_values ?? [];

        $changes = [];

        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            $changes[] = [
                'field' => (string) $field,
                'old' => $this->formatValue($old[$field] ?? null),
                'new' => $this->formatValue($new[$field] ?? null),
            ];
        }

        return $changes;
    }

    private function formatValue(mixed $value): string
    {
        return match (true) {
            $value === null => '—',
            is_bool($value) => $value ? 'Sí' : 'No',
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
