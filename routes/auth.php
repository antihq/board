<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');

    Route::livewire('register', 'pages::auth.register');
});

Route::post('logout', App\Livewire\Actions\Logout::class);
