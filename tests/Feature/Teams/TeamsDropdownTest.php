<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows user to switch teams from the dropdown', function () {
    $user = User::factory()->has(Team::factory()->count(2))->create();
    $teamA = $user->teams->first();
    $teamB = $user->teams->skip(1)->first();

    $user->switchTeam($teamA);
    expect($user->fresh()->currentTeam->is($teamA))->toBeTrue();

    Livewire::actingAs($user)
        ->test('teams-dropdown')
        ->call('switchTeam', $teamB->id)
        ->assertRedirect('/dashboard');

    expect($user->fresh()->currentTeam->is($teamB))->toBeTrue();
});

it('allows user to switch to a team they are a member of', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->for($owner)->create();
    $team->addMember($member);

    $member->switchTeam($team);
    expect($member->fresh()->currentTeam->is($team))->toBeTrue();

    Livewire::actingAs($member)
        ->test('teams-dropdown')
        ->call('switchTeam', $team->id)
        ->assertRedirect('/dashboard');

    expect($member->fresh()->currentTeam->is($team))->toBeTrue();
});

it('prevents user from switching to a team they do not own', function () {
    $user = User::factory()->has(Team::factory()->count(2))->create();
    $teamA = $user->teams->first();
    $otherTeam = Team::factory()->create();

    $user->switchTeam($teamA);
    expect($user->fresh()->currentTeam->is($teamA))->toBeTrue();

    Livewire::actingAs($user)
        ->test('teams-dropdown')
        ->call('switchTeam', $otherTeam->id)
        ->assertForbidden();

    expect($user->fresh()->currentTeam->is($otherTeam))->toBeFalse();
});
