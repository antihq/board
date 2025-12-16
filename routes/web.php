<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile');
    Route::livewire('settings/appearance', 'pages::settings.appearance');
});

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');

    Route::livewire('register', 'pages::auth.register');
});

Route::post('logout', App\Livewire\Actions\Logout::class);
