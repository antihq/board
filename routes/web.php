<?php

use App\Http\Controllers\TeamInvitationAcceptController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard/', 'pages::dashboard');
    Route::livewire('projects', 'pages::projects.index');
    Route::livewire('tasks', 'pages::tasks');

    Route::livewire('projects/{project}', 'pages::projects.show');
    Route::livewire('tasks/{task}', 'pages::tasks.show');
    Route::livewire('tasks/{task}/edit', 'pages::tasks.edit');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile');
    Route::livewire('settings/password', 'pages::settings.password');
    Route::livewire('settings/appearance', 'pages::settings.appearance');

    Route::livewire('teams/create', 'pages::teams.create');
    Route::livewire('teams/{team}/settings/members', 'pages::teams.settings.members');
    Route::livewire('teams/{team}/settings/general', 'pages::teams.settings.general');
    Route::livewire('teams/{team}', 'pages::teams.settings.general');

    Route::get('teams/invitations/{invitation}/accept', TeamInvitationAcceptController::class)
        ->middleware('signed')
        ->name('teams.invitations.accept');
});

require __DIR__.'/auth.php';

require __DIR__.'/billing.php';
