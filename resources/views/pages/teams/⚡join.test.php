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

it('prevents joining when invitation code has reached max uses', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['invitation_code_max_uses' => 1, 'invitation_code_uses_count' => 1]);

    Livewire::actingAs($user)
        ->test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->assertStatus(403);
});

it('increments invitation_code_uses_count when user joins', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['invitation_code_max_uses' => 10, 'invitation_code_uses_count' => 0]);

    Livewire::actingAs($user)
        ->test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->call('join');

    $team->refresh();
    expect($team->invitation_code_uses_count)->toBe(1);
});
