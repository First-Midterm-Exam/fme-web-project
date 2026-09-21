<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterionCheck extends Model
{
    use HasFactory;

    public const STATUS_PENDIENTE = 'Pendiente';

    public const STATUS_CUMPLE = 'Cumple';

    public const STATUS_NO_CUMPLE = 'No cumple';

    public const STATUS_NO_APLICA = 'No aplica';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'practice_evaluation_id',
        'practice_criterion_id',
        'status',
        'notes',
        'evaluated_by',
        'evaluated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evaluated_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDIENTE,
            self::STATUS_CUMPLE,
            self::STATUS_NO_CUMPLE,
            self::STATUS_NO_APLICA,
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_CUMPLE => 'bg-success',
            self::STATUS_NO_CUMPLE => 'bg-danger',
            self::STATUS_NO_APLICA => 'bg-secondary',
            default => 'bg-secondary bg-opacity-50',
        };
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
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function isPendiente(): bool
    {
        return $this->status === self::STATUS_PENDIENTE;
    }

    public function isNoAplica(): bool
    {
        return $this->status === self::STATUS_NO_APLICA;
    }
}
