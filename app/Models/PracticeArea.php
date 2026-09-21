<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeArea extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * @return HasMany<Practice, $this>
     */
    public function practices(): HasMany
    {
        return $this->hasMany(Practice::class)->orderBy('level')->orderBy('code');
    }
}
