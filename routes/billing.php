<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('subscription-required', 'pages::billing.subscription-required');

    Route::livewire('subscribe', 'pages::billing.subscribe');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('billing-portal', 'pages::billing.billing-portal');
});
