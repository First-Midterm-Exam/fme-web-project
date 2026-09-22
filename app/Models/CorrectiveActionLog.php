<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectiveActionLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corrective_action_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'description',
    ];

    /**
     * @return BelongsTo<CorrectiveAction, $this>
     */
    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(CorrectiveAction::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
