<?php

namespace App\Models;

use Database\Factories\PracticeCriterionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeCriterion extends Model
{
    /** @use HasFactory<PracticeCriterionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'practice_id',
        'code',
        'description',
        'orden',
        'required',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'required' => 'boolean',
            'estado' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Practice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    /**
     * @param  Builder<PracticeCriterion>  $query
     */
    public function scopeActivos(Builder $query): void
    {
        $query->where('estado', true);
    }
}
