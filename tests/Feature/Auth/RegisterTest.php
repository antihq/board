<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('renders the registration screen', function () {
    $response = get('/register');

    $response->assertOk();
    $response->assertSee('Create an account');
});

it('creates a user with personal team', function () {
    Mail::fake();

    Livewire::test('pages::auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::first();
    expect($user)->not->toBeNull();

    $team = $user->currentTeam;
    expect($team)->not->toBeNull();
    expect($team->user->is($user))->toBeTrue();
    expect($team->personal)->toBeTrue();
});
