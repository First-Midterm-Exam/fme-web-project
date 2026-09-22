<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Gap extends Model
{
    use HasFactory;

    public const STATUS_ABIERTO = 'Abierto';

    public const STATUS_EN_PROGRESO = 'En progreso';

    public const STATUS_RESUELTO = 'Resuelto';

    public const STATUS_VERIFICADO = 'Verificado';

    public const STATUS_CERRADO = 'Cerrado';

    public const SEVERITY_BAJA = 'Baja';

    public const SEVERITY_MEDIA = 'Media';

    public const SEVERITY_ALTA = 'Alta';

    public const SEVERITY_CRITICA = 'Crítica';

    /**
     * Allowed lifecycle transitions (RF-29).
     * Secuencia estricta: Abierto -> En progreso -> Resuelto -> Verificado.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_ABIERTO => [self::STATUS_EN_PROGRESO],
        self::STATUS_EN_PROGRESO => [self::STATUS_RESUELTO, self::STATUS_ABIERTO],
        self::STATUS_RESUELTO => [self::STATUS_VERIFICADO, self::STATUS_EN_PROGRESO],
        self::STATUS_VERIFICADO => [self::STATUS_CERRADO],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'practice_evaluation_id',
        'practice_criterion_id',
        'evidence_id',
        'title',
        'description',
        'status',
        'severity',
        'assigned_to_id',
        'due_date',
        'generated_by',
        'closed_by',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PracticeEvaluation, $this>
     */
    public function practiceEvaluation(): BelongsTo
    {
        return $this->belongsTo(PracticeEvaluation::class);
    }

    /**
     * @return BelongsTo<PracticeCriterion, $this>
     */
    public function practiceCriterion(): BelongsTo
    {
        return $this->belongsTo(PracticeCriterion::class);
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @return HasMany<GapLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(GapLog::class)->latest();
    }

    /**
     * Alias for logs relation to fulfill bitacora requirements.
     *
     * @return HasMany<GapLog, $this>
     */
    public function bitacora(): HasMany
    {
        return $this->logs();
    }

    /**
     * @return HasMany<CorrectiveAction, $this>
     */
    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class)->latest();
    }

    /**
     * Get the current active corrective action if any.
     */
    public function activeCorrectiveAction(): ?CorrectiveAction
    {
        return $this->correctiveActions->first(fn (CorrectiveAction $action) => $action->isActive());
    }

    /**
     * Check if the gap has an active corrective action.
     */
    public function hasActiveCorrectiveAction(): bool
    {
        return $this->activeCorrectiveAction() !== null;
    }

    /**
     * Scope to filter gaps visible to the given user based on inherited project visibility.
     *
     * @param  Builder<Gap>  $query
     */
    public function scopeVisibleFor(Builder $query, User $user): void
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return;
        }

        $query->whereHas('practiceEvaluation.appraisal.project', function (Builder $q) use ($user): void {
            $q->visibleFor($user);
        });
    }

    /**
     * @param  Builder<Gap>  $query
     */
    public function scopeAbiertos(Builder $query): void
    {
        $query->whereNotIn('status', [self::STATUS_CERRADO, self::STATUS_VERIFICADO]);
    }

    /**
     * Verify if the gap is overdue (RF-30).
     */
    public function isOverdue(): bool
    {
        if (! $this->due_date) {
            return false;
        }

        if (in_array($this->status, [self::STATUS_RESUELTO, self::STATUS_VERIFICADO, self::STATUS_CERRADO], true)) {
            return false;
        }

        $dueDate = Carbon::parse($this->due_date);

        return $dueDate->isPast() && ! $dueDate->isToday();
    }

    /**
     * Check if a transition to the target status is valid.
     */
    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === $targetStatus) {
            return true;
        }

        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        return in_array($targetStatus, $allowed, true);
    }

    /**
     * Transition the gap status following the lifecycle (RF-29).
     *
     * @throws \DomainException
     */
    public function transitionTo(string $targetStatus, ?User $user = null, ?string $description = null): void
    {
        if ($this->status === $targetStatus) {
            return;
        }

        if (! $this->canTransitionTo($targetStatus)) {
            throw new \DomainException(
                "Transición de estado inválida: no se puede cambiar de '{$this->status}' a '{$targetStatus}' saltándose pasos del ciclo de vida."
            );
        }

        $oldStatus = $this->status;
        $this->update(['status' => $targetStatus]);

        $this->recordLog(
            user: $user,
            field: 'status',
            oldValue: $oldStatus,
            newValue: $targetStatus,
            description: $description ?? "Cambio de estado de '{$oldStatus}' a '{$targetStatus}'"
        );
    }

    /**
     * Record a change in the gap audit log (RNF-06).
     */
    public function recordLog(?User $user, string $field, ?string $oldValue, ?string $newValue, ?string $description = null): GapLog
    {
        return $this->logs()->create([
            'user_id' => $user?->id,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFICADO, self::STATUS_CERRADO => 'bg-success',
            self::STATUS_RESUELTO => 'bg-info text-dark',
            self::STATUS_EN_PROGRESO => 'bg-primary',
            default => 'bg-warning text-dark',
        };
    }

    public function severityBadgeColor(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICA => 'bg-danger text-white',
            self::SEVERITY_ALTA => 'bg-warning text-dark',
            self::SEVERITY_MEDIA => 'bg-info text-dark',
            self::SEVERITY_BAJA => 'bg-secondary text-white',
            default => 'bg-secondary text-white',
        };
    }

    public static function generateNextCode(): string
    {
        $latestCode = static::query()
            ->where('code', 'LIKE', 'GAP-%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTR(code, 5) AS INTEGER) DESC')
            ->value('code');

        if (! $latestCode) {
            return 'GAP-0001';
        }

        return sprintf('GAP-%04d', (int) substr($latestCode, 4) + 1);
    }
}
