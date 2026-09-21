<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Practice extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'practice_area_id',
        'code',
        'name',
        'level',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    /**
     * Practice Area that groups this practice.
     *
     * @return BelongsTo<PracticeArea, $this>
     */
    public function practiceArea(): BelongsTo
    {
        return $this->belongsTo(PracticeArea::class);
    }

    /**
     * Appraisals that include this practice in their scope.
     *
     * @return BelongsToMany<Appraisal, $this>
     */
    public function appraisals(): BelongsToMany
    {
        return $this->belongsToMany(Appraisal::class, 'appraisal_scope')
            ->withTimestamps();
    }

    /**
     * Evaluation assessments for this practice across appraisals.
     *
     * @return HasMany<PracticeAssessment, $this>
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(PracticeAssessment::class);
    }

    /**
     * Criteria belonging to this practice.
     *
     * @return HasMany<PracticeCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(PracticeCriterion::class)->orderBy('code');
    }
}
