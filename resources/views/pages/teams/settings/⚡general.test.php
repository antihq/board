<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows team owner to edit team name and handle', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'name' => 'Original Name']);

    Livewire::actingAs($user)->test('pages::teams.settings.general', ['team' => $team])
        ->set('name', 'Updated Team Name')
        ->set('handle', 'updated-team-handle')
        ->call('save');

    $team->refresh();
    expect($team->name)->toBe('Updated Team Name');
    expect($team->handle)->toBe('updated-team-handle');
});

it('denies non-team owner from editing team name and handle', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id, 'name' => 'Original Name']);

    Livewire::actingAs($nonOwner)->test('pages::teams.settings.general', ['team' => $team])
        ->assertForbidden();
});

it('validates handle uniqueness during team edit', function () {
    $user = User::factory()->create();
    $existingTeam = Team::factory()->create(['user_id' => $user->id, 'handle' => 'team-one']);
    $team2 = Team::factory()->create(['user_id' => $user->id, 'handle' => 'team-two']);

    Livewire::actingAs($user)->test('pages::teams.settings.general', ['team' => $team2])
        ->set('handle', 'team-one')
        ->call('save')
        ->assertHasErrors(['handle' => 'unique']);
});
