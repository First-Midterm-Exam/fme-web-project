<?php

namespace App\Http\Resources;

use App\Models\Appraisal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin Appraisal
 */
class AppraisalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->name,
            'proyecto' => $this->project->name,
            'nivel_objetivo' => $this->target_level,
            'fecha_meta' => Carbon::parse($this->target_date)->format('Y-m-d'),
            'estado' => $this->status,
        ];
    }
}
