<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'start_date',
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
            'start_date' => 'date',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany<Appraisal, $this>
     */
    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class);
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeVisibleFor(Builder $query, User $user): void
    {
        if ($user->tieneRol(Rol::ADMINISTRADOR, Rol::GESTOR_PROCESOS)) {
            return;
        }

        $query->whereHas('users', function (Builder $q) use ($user): void {
            $q->where('users.id', $user->id);
        });
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'activo');
    }

    public function isActive(): bool
    {
        return $this->status === 'activo';
    }
}
