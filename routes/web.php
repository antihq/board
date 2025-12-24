<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::redirect('/', '/login');

Route::livewire('{team:handle}/join/{invitation_code}', 'pages::teams.join')->name('teams.join');

Route::middleware(['auth'])->group(function () {
    Route::livewire('{team:handle}/account/profile', 'pages::account.profile')->name('teams.account.profile');
    Route::livewire('{team:handle}/account/appearance', 'pages::account.appearance')->name('teams.account.appearance');
    Route::livewire('{team:handle}/account/devices', 'pages::account.devices')->name('teams.account.devices');
});

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');

    Route::livewire('register', 'pages::auth.register');

    Route::get('device-login/{user}', function ($userId) {
        if (! URL::hasValidSignature(request())) {
            abort(403, 'Invalid or expired login link');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login')->with('error', 'The user account associated with this login link no longer exists.');
        }

        Auth::loginUsingId($userId);

        return redirect()->route('dashboard');
    })->name('auth.device-login');
});

Route::post('logout', Logout::class);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('{team:handle}', 'pages::teams.show')->name('teams.show');
    Route::livewire('{team:handle}/settings/general', 'pages::teams.settings.general')->name('teams.settings.general');
    Route::livewire('{team:handle}/settings/auto-close', 'pages::teams.settings.auto-close')->name('teams.settings.auto-close');
    Route::livewire('{team:handle}/settings/members', 'pages::teams.settings.members')->name('teams.settings.members');
    Route::livewire('{team:handle}/tasks', 'pages::tasks')->name('tasks');
    Route::livewire('{team:handle}/saved-tasks', 'pages::teams.saved-tasks')->name('teams.saved-tasks');
    Route::livewire('{team:handle}/{project:handle}', 'pages::projects.show')->name('projects.show');
    Route::livewire('{team:handle}/{project:handle}/settings/general', 'pages::projects.settings.general')->name('projects.settings.general');
    Route::livewire('{team:handle}/{project:handle}/settings/auto-close', 'pages::projects.settings.auto-close')->name('projects.settings.auto-close');
    Route::livewire('{team:handle}/{project:handle}/settings/access', 'pages::projects.settings.access')->name('projects.settings.access');
});
