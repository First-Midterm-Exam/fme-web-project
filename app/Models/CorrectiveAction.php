<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $gap_id
 * @property string $description
 * @property int $responsible_id
 * @property string|Carbon $due_date
 * @property int $progress_percent
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Gap $gap
 * @property-read User $responsible
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
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'progress_percent' => 'integer',
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
     * Check if the corrective action is still active (not closed).
     */
    public function isActive(): bool
    {
        return $this->status !== self::STATUS_CERRADA && $this->progress_percent < 100;
    }

    /**
     * Check if the corrective action is overdue.
     */
    public function isOverdue(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        return Carbon::parse($this->due_date)->isPast();
    }
}
