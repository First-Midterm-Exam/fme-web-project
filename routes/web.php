<?php

use App\Livewire\Users\UserManagement;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('users', UserManagement::class)
    ->middleware(['auth', 'can:viewAny,'.User::class])
    ->name('users.index');

require __DIR__.'/auth.php';
