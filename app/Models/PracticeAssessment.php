<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAssessment extends Model
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
     * List of official statuses defined for appraisal practice evaluation.
     *
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

    /**
     * Get badge color class for a given assessment status.
     */
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
     * Appraisal where this assessment belongs.
     *
     * @return BelongsTo<Appraisal, $this>
     */
    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    /**
     * Practice being assessed.
     *
     * @return BelongsTo<Practice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
