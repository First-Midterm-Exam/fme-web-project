<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gap extends Model
{
    use HasFactory;

    public const STATUS_ABIERTO = 'Abierto';

    public const STATUS_CERRADO = 'Cerrado';

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
        'generated_by',
    ];

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
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @param  Builder<Gap>  $query
     */
    public function scopeAbiertos(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_CERRADO);
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
