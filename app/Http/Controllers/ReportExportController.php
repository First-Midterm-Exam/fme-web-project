<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Services\Reports\CorrectiveActionReportService;
use App\Services\Reports\EvidenceReportService;
use App\Services\Reports\GapReportService;
use App\Services\Reports\PracticeReportService;
use App\Services\Reports\ReadinessReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ReportExportController extends Controller
{
    public function __construct(
        private readonly PracticeReportService $practiceService,
        private readonly EvidenceReportService $evidenceService,
        private readonly GapReportService $gapService,
        private readonly CorrectiveActionReportService $actionService,
        private readonly ReadinessReportService $readinessService,
    ) {}

    public function __invoke(Appraisal $appraisal, string $tipo): Response
    {
        Gate::authorize('exportReport', $appraisal);

        $allowedTypes = ['practicas', 'evidencias', 'gaps', 'acciones', 'readiness'];

        if (! in_array($tipo, $allowedTypes, true)) {
            abort(404, 'Tipo de reporte no reconocido.');
        }

        $data = match ($tipo) {
            'practicas' => $this->practiceService->getData($appraisal),
            'evidencias' => $this->evidenceService->getData($appraisal),
            'gaps' => $this->gapService->getData($appraisal),
            'acciones' => $this->actionService->getData($appraisal),
            'readiness' => $this->readinessService->getData($appraisal),
        };

        $view = "pdf.{$tipo}";
        $nombre = Str::slug("reporte-{$tipo}-{$appraisal->name}").'.pdf';

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download($nombre);
    }
}
