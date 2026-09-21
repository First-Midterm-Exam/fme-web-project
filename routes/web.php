<?php

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

foreach (Modulos::pendientes() as $modulo) {
    Route::view($modulo['uri'], 'modulos.index', ['modulo' => $modulo])
        ->middleware(['auth', 'can:'.$modulo['capacidad']])
        ->name($modulo['ruta']);
}

require __DIR__.'/auth.php';
