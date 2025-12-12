<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('adds a new team for user with valid data', function () {
    /** @var User $user */
    $user = User::factory()->create();
    actingAs($user);

    $teamName = 'Acme Inc';
    $response = Livewire::test('pages::teams.create')
        ->set('name', $teamName)
        ->call('create');

    $response->assertHasNoErrors();
    $user->refresh();
    $team = $user->teams()->where('name', $teamName)->first();
    expect($team)->not->toBeNull();
    expect($user->currentTeam->is($team))->toBeTrue();
    $response->assertRedirect('/dashboard');
});

it('shows validation errors for missing or invalid team name', function () {
    /** @var User $user */
    $user = User::factory()->create();
    actingAs($user);

    $response = Livewire::test('pages::teams.create')
        ->set('name', '')
        ->call('create');

    $response->assertHasErrors(['name']);
});

it('guests cannot add teams', function () {
    $teamName = 'Gamma Ltd';
    $response = Livewire::test('pages::teams.create')
        ->set('name', $teamName)
        ->call('create');

    $response->assertForbidden();
});
