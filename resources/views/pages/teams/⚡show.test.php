<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows team owner to view team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test('pages::teams.show', ['team' => $team])
        ->assertOk();
});

it('denies non-team owner from viewing team', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);

    Livewire::actingAs($nonOwner)->test('pages::teams.show', ['team' => $team])
        ->assertForbidden();
});

it('allows team owner to remove team member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($owner)->test('pages::teams.show', ['team' => $team])
        ->call('removeTeamMember', $member->id);

    $team->refresh();
    expect($team->users()->where('users.id', $member->id)->exists())->toBeFalse();
});

it('allows team admin to remove team member', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($admin->id, ['role' => 'admin']);
    $team->users()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($admin)->test('pages::teams.show', ['team' => $team])
        ->call('removeTeamMember', $member->id);

    $team->refresh();
    expect($team->users()->where('users.id', $member->id)->exists())->toBeFalse();
});

it('does not allow team member to remove other members', function () {
    $owner = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member1->id, ['role' => 'member']);
    $team->users()->attach($member2->id, ['role' => 'member']);

    Livewire::actingAs($member1)->test('pages::teams.show', ['team' => $team])
        ->call('removeTeamMember', $member2->id);

    $team->refresh();
    expect($team->users()->where('users.id', $member2->id)->exists())->toBeTrue();
});

it('does not allow removing team owner', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($admin->id, ['role' => 'admin']);

    $originalOwnerId = $team->user_id;

    Livewire::actingAs($admin)->test('pages::teams.show', ['team' => $team])
        ->call('removeTeamMember', $owner->id);

    $team->refresh();
    expect($team->user_id)->toBe($originalOwnerId);
});

it('allows team member to leave team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($member)->test('pages::teams.show', ['team' => $team])
        ->call('leaveTeam');

    $team->refresh();
    expect($team->users()->where('users.id', $member->id)->exists())->toBeFalse();
});

it('allows team admin to leave team', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($admin->id, ['role' => 'admin']);

    Livewire::actingAs($admin)->test('pages::teams.show', ['team' => $team])
        ->call('leaveTeam');

    $team->refresh();
    expect($team->users()->where('users.id', $admin->id)->exists())->toBeFalse();
});

it('does not allow team owner to leave team', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);

    Livewire::actingAs($owner)->test('pages::teams.show', ['team' => $team])
        ->call('leaveTeam');

    $team->refresh();
    expect($team->user_id)->toBe($owner->id);
});
