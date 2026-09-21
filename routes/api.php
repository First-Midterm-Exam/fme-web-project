<?php

use App\Http\Controllers\Api\AppraisalController;
use App\Http\Controllers\Api\AsistenteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RevisionFormatoController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login');

    Route::get('asistente/archivos/{archivo}/descarga', [AsistenteController::class, 'descargar'])
        ->where('archivo', 'rep-[a-z0-9]+')
        ->name('asistente.archivos.descarga');

    Route::middleware(['auth:sanctum', 'usuario.activo'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('appraisals', [AppraisalController::class, 'index'])->name('appraisals.index');

        Route::post('documentos/revision-formato', RevisionFormatoController::class)
            ->name('documentos.revision-formato');

        Route::post('asistente/consultas', [AsistenteController::class, 'consultar'])
            ->middleware('throttle:asistente')
            ->name('asistente.consultas');
    });
});
