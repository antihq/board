<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('an authenticated user can edit their team name', function () {
    $user = User::factory()->create();
    $team = Team::factory()
        ->for($user)
        ->create([
            'name' => 'Old Name',
        ]);

    Livewire::actingAs($user)
        ->test('pages::teams.settings.general', ['team' => $team])
        ->set('name', 'New Name')
        ->call('edit')
        ->assertHasNoErrors();

    expect($team->fresh()->name)->toBe('New Name');
});

it('cannot update team name to empty', function () {
    $user = User::factory()->create();
    $team = Team::factory()
        ->for($user)
        ->create([
            'name' => 'Old Name',
        ]);

    Livewire::actingAs($user)
        ->test('pages::teams.settings.general', ['team' => $team])
        ->set('name', '')
        ->call('edit')
        ->assertHasErrors(['name' => 'required']);
});

it('forbids non-owners from editing team name', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $team = Team::factory()
        ->for($owner)
        ->create([
            'name' => 'Old Name',
        ]);

    Livewire::actingAs($nonOwner)
        ->test('pages::teams.settings.general', ['team' => $team])
        ->assertForbidden();

    expect($team->fresh()->name)->toBe('Old Name');
});

it('returns a successful response for team details page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::teams.settings.general', ['team' => $team])
        ->assertOk();
});

it('forbids team members (non-owners) from accessing team details page', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->for($owner)->create();
    $team->addMember($member);
    $member->switchTeam($team);

    Livewire::actingAs($member)
        ->test('pages::teams.settings.general', ['team' => $team])
        ->assertForbidden();
});
