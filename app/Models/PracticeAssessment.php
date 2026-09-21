<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAssessment extends Model
{
    use HasFactory;

    public const STATUS_NO_EVALUADA = 'No evaluada';

    public const STATUS_CUMPLE = 'Cumple';

    public const STATUS_CUMPLE_PARCIALMENTE = 'Cumple parcialmente';

    public const STATUS_NO_CUMPLE = 'No cumple';

    public const STATUS_NO_APLICA = 'No aplica';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'appraisal_id',
        'practice_id',
        'status',
    ];

    /**
     * Appraisal where this assessment belongs.
     *
     * @return BelongsTo<Appraisal, $this>
     */
    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    /**
     * Practice being assessed.
     *
     * @return BelongsTo<Practice, $this>
     */
    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
