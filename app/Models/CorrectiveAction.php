<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $gap_id
 * @property string $description
 * @property int $responsible_id
 * @property string|Carbon $due_date
 * @property int $progress_percent
 * @property string $status
 * @property int|null $solution_evidence_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Gap $gap
 * @property-read User $responsible
 * @property-read Evidence|null $solutionEvidence
 */
class CorrectiveAction extends Model
{
    use HasFactory;

    public const STATUS_ABIERTA = 'abierta';

    public const STATUS_EN_PROGRESO = 'en_progreso';

    public const STATUS_CERRADA = 'cerrada';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gap_id',
        'description',
        'responsible_id',
        'due_date',
        'progress_percent',
        'status',
        'solution_evidence_id',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'progress_percent' => 'integer',
        'solution_evidence_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Gap, $this>
     */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function solutionEvidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class, 'solution_evidence_id');
    }

    /**
     * @return HasMany<CorrectiveActionLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CorrectiveActionLog::class)->latest();
    }

    public function recordLog(?User $user, string $field, ?string $oldValue, ?string $newValue, ?string $description = null): CorrectiveActionLog
    {
        return $this->logs()->create([
            'user_id' => $user?->id,
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }

    public function isActive(): bool
    {
        return $this->status !== self::STATUS_CERRADA && $this->progress_percent < 100;
    }

    public function isOverdue(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return Carbon::parse($this->due_date)->isPast();
    }
}
