<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeEvaluation extends Model
{
    use HasFactory;

    public const STATUS_NO_EVALUADA = 'No evaluada';

    public const STATUS_NO_CUMPLE = 'No cumple';

    public const STATUS_PARCIAL = 'Parcial';

    public const STATUS_CUMPLE_PARCIALMENTE = 'Cumple parcialmente';

    public const STATUS_CUMPLE = 'Cumple';

    public const STATUS_VERIFICADA = 'Verificada';

    public const STATUS_NO_APLICA = 'No aplica';

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NO_EVALUADA,
            self::STATUS_NO_CUMPLE,
            self::STATUS_PARCIAL,
            self::STATUS_CUMPLE,
            self::STATUS_VERIFICADA,
        ];
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_VERIFICADA => 'bg-success',
            self::STATUS_CUMPLE => 'bg-info text-dark',
            self::STATUS_PARCIAL, self::STATUS_CUMPLE_PARCIALMENTE => 'bg-warning text-dark',
            self::STATUS_NO_CUMPLE => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'appraisal_id',
        'practice_id',
        'status',
    ];

    /**
     * @return BelongsTo<Appraisal, $this>
     */
    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    /**
     * @return BelongsTo<Practice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * @return HasMany<CriterionCheck, $this>
     */
    public function criterionChecks(): HasMany
    {
        return $this->hasMany(CriterionCheck::class);
    }

    public function compliancePercentage(): int
    {
        $applicable = $this->applicableCriteriaCount();

        if ($applicable === 0) {
            return 0;
        }

        return (int) round($this->metCriteriaCount() / $applicable * 100);
    }

    public function applicableCriteriaCount(): int
    {
        $notApplicable = $this->criterionChecks
            ->where('status', CriterionCheck::STATUS_NO_APLICA)
            ->count();

        return max($this->activeCriteriaCount() - $notApplicable, 0);
    }

    public function metCriteriaCount(): int
    {
        return $this->criterionChecks
            ->where('status', CriterionCheck::STATUS_CUMPLE)
            ->count();
    }

    public function activeCriteriaCount(): int
    {
        return $this->practice->criteria->where('estado', true)->count();
    }
}
