<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Criterio extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'criterios';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'practica_id',
        'descripcion',
        'orden',
        'estado',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'practica_id' => 'integer',
        'orden' => 'integer',
        'estado' => 'boolean',
    ];

    /**
     * Obtener la práctica a la que pertenece el criterio.
     */
    public function practica(): BelongsTo
    {
        return $this->belongsTo(Practica::class);
    }
}
