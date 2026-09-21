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
     * @return BelongsTo<PracticeArea, $this>
     */
    public function practiceArea(): BelongsTo
    {
        return $this->belongsTo(PracticeArea::class);
    }

    /**
     * @return BelongsToMany<Appraisal, $this, AppraisalScope>
     */
    public function appraisals(): BelongsToMany
    {
        return $this->belongsToMany(Appraisal::class, 'appraisal_scopes')
            ->using(AppraisalScope::class)
            ->withPivot(['practice_area_id', 'descripcion', 'incluida'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PracticeEvaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(PracticeEvaluation::class);
    }

    /**
     * @return HasMany<PracticeCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(PracticeCriterion::class)->orderBy('orden')->orderBy('code');
    }
}
