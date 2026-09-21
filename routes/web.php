<?php

use App\Livewire\Appraisals\AppraisalManagement;
use App\Livewire\Appraisals\AppraisalScopeSelection;
use App\Livewire\Projects\ProjectDetail;
use App\Livewire\Projects\ProjectManagement;
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

foreach (Modulos::pendientes() as $modulo) {
    Route::view($modulo['uri'], 'modulos.index', ['modulo' => $modulo])
        ->middleware(['auth', 'can:'.$modulo['capacidad']])
        ->name($modulo['ruta']);
}

require __DIR__.'/auth.php';
