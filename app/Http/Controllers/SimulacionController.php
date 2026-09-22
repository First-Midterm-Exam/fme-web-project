<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\ResultadoSimulacionDTO;
use App\Http\Requests\EjecutarSimulacionRequest;
use App\Models\Appraisal;
use App\Models\AppraisalSimulation;
use App\Services\AppraisalSimulationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SimulacionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AppraisalSimulationService $simulationService,
    ) {}

    public function index(Appraisal $appraisal): View
    {
        $this->authorize('ver-readiness');
        $this->authorize('view', $appraisal);

        $simulaciones = $appraisal->simulations()
            ->with(['executor', 'readinessMeasurement'])
            ->latest('id')
            ->limit(20)
            ->get();

        $ultima = $simulaciones->first();

        return view('simulaciones.index', [
            'appraisal' => $appraisal->loadMissing('project'),
            'simulaciones' => $simulaciones,
            'resultado' => $ultima instanceof AppraisalSimulation ? ResultadoSimulacionDTO::desdeSimulacion($ultima)->toArray() : null,
        ]);
    }

    public function store(EjecutarSimulacionRequest $request, Appraisal $appraisal): JsonResponse
    {
        $resultado = $this->simulationService->ejecutarSimulacion(
            $appraisal->id,
            $request->integer('nivelObjetivo'),
            $request->user(),
        );

        return response()->json([
            'resultadoSimulacion' => $resultado->toArray(),
        ]);
    }
}
