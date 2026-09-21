<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appraisal extends Model
{
    use HasFactory;

    public const MODELO_REFERENCIA = 'CMMI V3.0';

    public const STATUS_BORRADOR = 'borrador';

    public const STATUS_ACTIVO = 'activo';

    public const STATUS_CERRADO = 'cerrado';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'domain',
        'target_level',
        'target_date',
        'status',
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
            'target_date' => 'date',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /**
     * Project being evaluated by this appraisal.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * CMMI practices included in this appraisal's scope.
     *
     * @return BelongsToMany<Practice, $this>
     */
    public function practices(): BelongsToMany
    {
        return $this->belongsToMany(Practice::class, 'appraisal_scope')
            ->withTimestamps();
    }

    /**
     * Practice assessments generated for this appraisal.
     *
     * @return HasMany<PracticeAssessment, $this>
     */
    public function practiceAssessments(): HasMany
    {
        return $this->hasMany(PracticeAssessment::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to filter appraisals visible to the given user.
     * Delegates entirely to Project::visibleFor($user).
     *
     * @param  Builder<Appraisal>  $query
     */
    public function scopeVisibleFor(Builder $query, User $user): void
    {
        $query->whereHas('project', function (Builder $q) use ($user): void {
            $q->visibleFor($user);
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isBorrador(): bool
    {
        return $this->status === self::STATUS_BORRADOR;
    }

    public function isActivo(): bool
    {
        return $this->status === self::STATUS_ACTIVO;
    }

    public function isCerrado(): bool
    {
        return $this->status === self::STATUS_CERRADO;
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVO => 'bg-success',
            self::STATUS_CERRADO => 'bg-secondary',
            default => 'bg-warning text-dark',
        };
    }
}
