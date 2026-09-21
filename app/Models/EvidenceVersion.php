<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceVersion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'evidence_id',
        'number',
        'file_public_id',
        'file_url',
        'file_resource_type',
        'file_format',
        'file_original_name',
        'file_size',
        'uploaded_by',
        'uploaded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function formattedSize(): string
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $tamano = (float) $this->file_size;
        $indice = 0;

        while ($tamano >= 1024 && $indice < count($unidades) - 1) {
            $tamano /= 1024;
            $indice++;
        }

        return ($indice === 0 ? (string) (int) $tamano : number_format($tamano, 1, ',', '.')).' '.$unidades[$indice];
    }
}
