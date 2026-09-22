<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GapLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gap_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'description',
    ];

    /**
     * @return BelongsTo<Gap, $this>
     */
    public function gap(): BelongsTo
    {
        return $this->belongsTo(Gap::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
