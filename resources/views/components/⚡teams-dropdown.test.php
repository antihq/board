<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('redirects to team show page when selecting valid team', function () {
    $user = User::factory()->has(Team::factory()->count(2))->create();
    $team = $user->teams()->first();
    $otherTeam = $user->teams()->skip(1)->first();

    Livewire::actingAs($user)->test('teams-dropdown', ['team' => $team])
        ->set('selectedTeamId', $otherTeam->id)
        ->assertRedirect(route('teams.show', ['team' => $otherTeam]));
});

it('aborts with 404 when selecting team user does not belong to', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $unauthorizedTeam = Team::factory()->create();

    Livewire::actingAs($user)->test('teams-dropdown', ['team' => $team])
        ->set('selectedTeamId', $unauthorizedTeam->id)
        ->assertStatus(404);
});

it('aborts with 404 when selecting non-existent team', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    Livewire::actingAs($user)->test('teams-dropdown', ['team' => $team])
        ->set('selectedTeamId', 999)
        ->assertStatus(404);
});

it('allows redirect to owned team', function () {
    $user = User::factory()->has(Team::factory()->count(2))->create();
    $team = $user->teams()->first();
    $ownedTeam = $user->teams()->skip(1)->first();

    Livewire::actingAs($user)->test('teams-dropdown', ['team' => $team])
        ->set('selectedTeamId', $ownedTeam->id)
        ->assertRedirect(route('teams.show', ['team' => $ownedTeam]));
});

it('allows redirect to joined team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->joinedTeams()->attach($team->id, ['role' => 'member']);

    $currentTeam = Team::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test('teams-dropdown', ['team' => $currentTeam])
        ->set('selectedTeamId', $team->id)
        ->assertRedirect(route('teams.show', ['team' => $team]));
});
