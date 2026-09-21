<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppraisalScope extends Pivot
{
    public $incrementing = true;

    protected $table = 'appraisal_scopes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'appraisal_id',
        'practice_area_id',
        'practice_id',
        'descripcion',
        'incluida',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incluida' => 'boolean',
        ];
    }

    /**
     * @param  iterable<int, int>  $practiceIds
     * @return array<int, array<string, mixed>>
     */
    public static function pivotFor(iterable $practiceIds): array
    {
        return Practice::whereKey(collect($practiceIds)->all())
            ->pluck('practice_area_id', 'id')
            ->map(fn (int $areaId): array => ['practice_area_id' => $areaId])
            ->all();
    }

    protected static function booted(): void
    {
        static::saving(function (self $scope): void {
            if ($scope->practice_area_id === null) {
                $scope->practice_area_id = Practice::whereKey($scope->practice_id)->value('practice_area_id');
            }
        });
    }

    /**
     * @return BelongsTo<Appraisal, $this>
     */
    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    /**
     * @return BelongsTo<PracticeArea, $this>
     */
    public function practiceArea(): BelongsTo
    {
        return $this->belongsTo(PracticeArea::class);
    }

    /**
     * @return BelongsTo<Practice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
