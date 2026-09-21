<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCriterioRequest;
use App\Models\Appraisal;
use App\Models\Practice;
use App\Models\PracticeCriterion;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CriterioController extends Controller
{
    use AuthorizesRequests;

    public function index(Appraisal $appraisal, Practice $practice): View
    {
        $this->autorizarContexto($appraisal, $practice);

        return view('criterios.index', [
            'appraisal' => $appraisal,
            'practice' => $practice,
            'criterios' => $practice->criteria()->get(),
        ]);
    }

    public function create(Appraisal $appraisal, Practice $practice): View
    {
        $this->autorizarContexto($appraisal, $practice);

        return view('criterios.create', [
            'appraisal' => $appraisal,
            'practice' => $practice,
        ]);
    }

    public function store(StoreCriterioRequest $request, Appraisal $appraisal, Practice $practice): RedirectResponse
    {
        $this->autorizarContexto($appraisal, $practice);

        $practice->criteria()->create($this->datosValidados($request));

        return redirect()
            ->route('criterios.index', [$appraisal, $practice])
            ->with('success', 'El criterio de evaluación fue registrado exitosamente.');
    }

    public function edit(Appraisal $appraisal, Practice $practice, PracticeCriterion $criterio): View
    {
        $this->autorizarContexto($appraisal, $practice, $criterio);

        return view('criterios.edit', [
            'appraisal' => $appraisal,
            'practice' => $practice,
            'criterio' => $criterio,
        ]);
    }

    public function update(StoreCriterioRequest $request, Appraisal $appraisal, Practice $practice, PracticeCriterion $criterio): RedirectResponse
    {
        $this->autorizarContexto($appraisal, $practice, $criterio);

        $criterio->update($this->datosValidados($request));

        return redirect()
            ->route('criterios.index', [$appraisal, $practice])
            ->with('success', 'El criterio de evaluación fue actualizado exitosamente.');
    }

    public function destroy(Appraisal $appraisal, Practice $practice, PracticeCriterion $criterio): RedirectResponse
    {
        $this->autorizarContexto($appraisal, $practice, $criterio);

        $criterio->delete();

        return redirect()
            ->route('criterios.index', [$appraisal, $practice])
            ->with('success', 'El criterio de evaluación fue eliminado exitosamente.');
    }

    private function autorizarContexto(Appraisal $appraisal, Practice $practice, ?PracticeCriterion $criterio = null): void
    {
        $this->authorize('view', $appraisal);

        abort_unless(
            $appraisal->practices()->where('practices.id', $practice->id)->exists(),
            404
        );

        abort_if($criterio !== null && $criterio->practice_id !== $practice->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidados(StoreCriterioRequest $request): array
    {
        $validated = $request->validated();

        return [
            'code' => $validated['code'],
            'description' => $validated['descripcion'],
            'orden' => (int) ($validated['orden'] ?? 0),
            'required' => $request->boolean('required'),
            'estado' => $request->boolean('estado'),
        ];
    }
}
