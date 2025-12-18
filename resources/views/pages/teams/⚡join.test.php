<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows authenticated user to join team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->call('join')
        ->assertRedirect(route('teams.show', ['team' => $team]));

    expect($user->joinedTeams()->where('teams.id', $team->id)->exists())
        ->toBeTrue();
});

it('prevents users from joining team twice', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    $user->joinedTeams()->attach($team->id, ['role' => 'member']);

    Livewire::actingAs($user)
        ->test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->assertRedirect(route('dashboard'));
});

it('sets member role when joining team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->call('join')
        ->assertRedirect(route('teams.show', ['team' => $team]));

    $membership = $user->joinedTeams()->where('teams.id', $team->id)->first();
    expect($membership->pivot->role)->toBe('member');
});

it('prevents access with invalid invitation code', function () {
    $team = Team::factory()->create();

    Livewire::test('pages::teams.join', ['team' => $team, 'invitation_code' => 'invalid'])
        ->assertStatus(403);
});
