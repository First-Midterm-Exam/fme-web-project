<?php

use App\Http\Controllers\CriterioController;
use App\Http\Controllers\EvidenceDownloadController;
use App\Http\Controllers\ReadinessMenuController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SimulacionController;
use App\Livewire\Appraisals\AppraisalManagement;
use App\Livewire\Appraisals\AppraisalPracticeList;
use App\Livewire\Appraisals\AppraisalReadinessScore;
use App\Livewire\Appraisals\AppraisalScopeSelection;
use App\Livewire\Appraisals\MissingActivitiesView;
use App\Livewire\Appraisals\PracticeChecklist;
use App\Livewire\Audit\AuditLogList;
use App\Livewire\Evidences\EvidenceDetail;
use App\Livewire\Evidences\EvidenceManagement;
use App\Livewire\Evidences\PendingEvidenceVerification;
use App\Livewire\Gaps\GapClosureValidation;
use App\Livewire\Gaps\GapDetail;
use App\Livewire\Gaps\GapManagement;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectManagement;
use App\Livewire\Reports\Traceability;
use App\Livewire\Users\UserManagement;
use App\Models\User;
use App\Support\Modulos;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('users', UserManagement::class)
    ->middleware(['auth', 'rol:administrador', 'can:viewAny,'.User::class])
    ->name('users.index');

Route::get('proyectos', ProjectManagement::class)
    ->middleware(['auth', 'can:ver-proyectos'])
    ->name('proyectos.index');

Route::get('proyectos/{project}', ProjectDetail::class)
    ->middleware(['auth'])
    ->name('proyectos.show');

Route::get('appraisals', AppraisalManagement::class)
    ->middleware(['auth', 'can:ver-appraisals'])
    ->name('appraisals.index');

Route::get('appraisals/{appraisal}/alcance', AppraisalScopeSelection::class)
    ->middleware(['auth'])
    ->name('appraisals.scope');

Route::get('appraisals/{appraisal}/practicas', AppraisalPracticeList::class)
    ->middleware(['auth'])
    ->name('appraisals.practices');

Route::get('appraisals/{appraisal}/practicas/{practice}/checklist', PracticeChecklist::class)
    ->middleware(['auth'])
    ->name('appraisals.practices.checklist');

Route::get('evidencias/verificacion', PendingEvidenceVerification::class)
    ->middleware(['auth', 'can:verificar-evidencias'])
    ->name('evidencias.verificacion');

Route::get('evidencias', EvidenceManagement::class)
    ->middleware(['auth', 'can:registrar-evidencia'])
    ->name('evidencias.index');

Route::get('evidencias/{evidence}', EvidenceDetail::class)
    ->middleware(['auth'])
    ->whereNumber('evidence')
    ->name('evidencias.show');

Route::get('evidencias/{evidence}/download', EvidenceDownloadController::class)
    ->name('evidencias.download');

Route::get('evidencias/{evidence}/versiones/{version}/download', EvidenceDownloadController::class)
    ->scopeBindings()
    ->name('evidencias.versions.download');

Route::get('gaps/validacion', GapClosureValidation::class)
    ->middleware(['auth', 'can:gestionar-gaps'])
    ->name('gaps.validacion');

Route::get('gaps', GapManagement::class)
    ->middleware(['auth'])
    ->name('gaps.index');

Route::get('gaps/{gap}', GapDetail::class)
    ->middleware(['auth'])
    ->whereNumber('gap')
    ->name('gaps.show');

Route::get('bitacora', AuditLogList::class)
    ->middleware(['auth', 'can:ver-bitacora'])
    ->name('bitacora.index');

Route::get('readiness', ReadinessMenuController::class)
    ->middleware(['auth', 'can:ver-readiness'])
    ->defaults('destino', 'appraisals.readiness')
    ->name('readiness.index');

Route::get('readiness/simulacion', ReadinessMenuController::class)
    ->middleware(['auth', 'can:ver-readiness'])
    ->defaults('destino', 'appraisals.simulaciones.index')
    ->name('readiness.simulacion');

Route::get('readiness/trazabilidad', Traceability::class)
    ->middleware(['auth', 'can:ver-readiness'])
    ->name('readiness.trazabilidad');

Route::get('appraisals/{appraisal}/gaps', GapManagement::class)
    ->middleware(['auth'])
    ->name('appraisals.gaps');

foreach (Modulos::pendientes() as $modulo) {
    Route::view($modulo['uri'], 'modulos.index', ['modulo' => $modulo])
        ->middleware(['auth', 'can:'.$modulo['capacidad']])
        ->name($modulo['ruta']);
}
Route::middleware(['auth', 'can:evaluar-cumplimiento'])
    ->prefix('appraisals/{appraisal}/practicas/{practice}/criterios')
    ->name('criterios.')
    ->controller(CriterioController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('nuevo', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{criterio}/editar', 'edit')->name('edit');
        Route::put('{criterio}', 'update')->name('update');
        Route::delete('{criterio}', 'destroy')->name('destroy');
    });
Route::get('/appraisals/{appraisal}/readiness', AppraisalReadinessScore::class)
    ->middleware(['auth'])
    ->name('appraisals.readiness');
Route::get('/appraisals/{appraisal}/missing-activities', MissingActivitiesView::class)
    ->name('appraisals.missing-activities')
    ->middleware(['auth']);

Route::middleware(['auth'])
    ->prefix('appraisals/{appraisal}/simulaciones')
    ->name('appraisals.simulaciones.')
    ->controller(SimulacionController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
    });

Route::get('appraisals/{appraisal}/reportes/{tipo}', ReportExportController::class)
    ->middleware(['auth'])
    ->where('tipo', 'practicas|evidencias|gaps|acciones|readiness')
    ->name('appraisals.reportes.export');

require __DIR__.'/auth.php';
