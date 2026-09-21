<?php

namespace App\Models;

use Database\Factories\PracticeCriterionFactory;
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
        'required',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }

    /**
     * Practice that this criterion belongs to.
     *
     * @return BelongsTo<Practice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
