<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvidenceStatus extends Model
{
    use HasFactory;

    public const REGISTRADA = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
    ];

    /**
     * @return HasMany<Evidence, $this>
     */
    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class, 'status_id');
    }
}
