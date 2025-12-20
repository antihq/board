<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile');
    Route::livewire('settings/appearance', 'pages::settings.appearance');
});

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');

    Route::livewire('register', 'pages::auth.register');
});

Route::post('logout', Logout::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('{team}', 'pages::teams.show')->name('teams.show');
    Route::livewire('{team}/join/{invitation_code}', 'pages::teams.join')->name('teams.join');
    Route::livewire('{team}/tasks', 'pages::tasks')->name('tasks');
    Route::livewire('{team}/{project}', 'pages::projects.show')->name('projects.show');
});
