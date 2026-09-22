<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadinessMeasurement extends Model
{
    public const STATUS_LISTO = 'Listo';

    public const STATUS_LISTO_CON_CONDICIONES = 'Listo con condiciones';

    public const STATUS_NO_LISTO = 'No listo';

    public const UMBRAL_CON_CONDICIONES = 85.0;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'appraisal_id',
        'appraisal_simulation_id',
        'status',
        'level',
        'score',
        'breakdown',
        'calculated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'score' => 'float',
            'breakdown' => 'array',
            'calculated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Appraisal, $this>
     */
    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    /**
     * @return BelongsTo<AppraisalSimulation, $this>
     */
    public function simulation(): BelongsTo
    {
        return $this->belongsTo(AppraisalSimulation::class, 'appraisal_simulation_id');
    }

    public static function statusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_LISTO => 'bg-success',
            self::STATUS_LISTO_CON_CONDICIONES => 'bg-warning text-dark',
            default => 'bg-danger',
        };
    }
}
