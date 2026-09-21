<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppraisalResource;
use App\Models\Appraisal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AppraisalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'estado' => ['nullable', Rule::in([
                Appraisal::STATUS_BORRADOR,
                Appraisal::STATUS_ACTIVO,
                Appraisal::STATUS_CERRADO,
            ])],
        ]);

        /** @var User $usuario */
        $usuario = $request->user();

        $appraisals = Appraisal::query()
            ->with('project')
            ->visibleFor($usuario)
            ->when($validated['estado'] ?? null, fn ($query, string $estado) => $query->where('status', $estado))
            ->orderBy('target_date')
            ->get();

        return AppraisalResource::collection($appraisals);
    }
}
