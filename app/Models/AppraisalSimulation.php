<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AppraisalSimulation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'appraisal_id',
        'executed_by',
        'target_level',
        'score',
        'evaluated_practices',
        'passed_practices',
        'gaps_found',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_level' => 'integer',
            'score' => 'float',
            'evaluated_practices' => 'integer',
            'passed_practices' => 'integer',
            'gaps_found' => 'array',
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
     * @return BelongsTo<User, $this>
     */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * @return HasOne<ReadinessMeasurement, $this>
     */
    public function readinessMeasurement(): HasOne
    {
        return $this->hasOne(ReadinessMeasurement::class);
    }
}
