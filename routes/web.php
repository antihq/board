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

    Route::livewire('{team:handle}', 'pages::teams.show')->name('teams.show');
    Route::livewire('{team:handle}/join/{invitation_code}', 'pages::teams.join')->name('teams.join');
    Route::livewire('{team:handle}/settings/general', 'pages::teams.settings.general')->name('teams.settings.general');
    Route::livewire('{team:handle}/settings/auto-close', 'pages::teams.settings.auto-close')->name('teams.settings.auto-close');
    Route::livewire('{team:handle}/tasks', 'pages::tasks')->name('tasks');
    Route::livewire('{team:handle}/saved-tasks', 'pages::teams.saved-tasks')->name('teams.saved-tasks');
    Route::livewire('{team:handle}/{project:handle}', 'pages::projects.show')->name('projects.show');
    Route::livewire('{team:handle}/{project:handle}/settings/general', 'pages::projects.settings.general')->name('projects.settings.general');
    Route::livewire('{team:handle}/{project:handle}/settings/auto-close', 'pages::projects.settings.auto-close')->name('projects.settings.auto-close');
});
