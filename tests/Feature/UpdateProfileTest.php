<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('displays the profile page', function () {
    $user = User::factory()->withPersonalTeam()->create();

    actingAs($user)->get('/settings/profile')->assertOk();
});

it('updates the profile information', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $component = Livewire::actingAs($user)->test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $component->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

it('keeps email verification status unchanged when email address is unchanged', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $component = Livewire::actingAs($user)->test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $component->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});
