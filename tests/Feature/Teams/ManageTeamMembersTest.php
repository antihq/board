<?php

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('unsets members current team when they are removed from that team', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = Team::factory()->for($owner)->create();
    $member = User::factory()->withPersonalTeam()->create();

    $team->addMember($member);
    $member->switchTeam($team);
    expect($member->currentTeam->is($team))->toBeTrue();

    Livewire::actingAs($owner)
        ->test('pages::teams.settings.members', ['team' => $team])
        ->call('removeMember', $member->id);

    expect($member->fresh()->current_team_id)->toBeNull();
});

it('allows owner to invite new members to team', function () {
    Notification::fake();

    $owner = User::factory()->withPersonalTeam()->create();
    $team = Team::factory()->for($owner)->create();
    $memberEmail = 'newmember@example.com';

    Livewire::actingAs($owner)
        ->test('pages::teams.settings.members', ['team' => $team])
        ->set('email', $memberEmail)
        ->call('sendInvitation')
        ->assertHasNoErrors();

    expect($team->invitations()->where('email', $memberEmail)->exists())->toBeTrue();
});

it('prevents duplicate invitations to same email', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = Team::factory()->for($owner)->create();
    $memberEmail = 'newmember@example.com';

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => $memberEmail,
    ]);

    Livewire::actingAs($owner)
        ->test('pages::teams.settings.members', ['team' => $team])
        ->set('email', $memberEmail)
        ->call('sendInvitation')
        ->assertHasErrors(['email' => 'The email has already been taken.']);
});

it('allows owner to revoke pending invitations', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $team = Team::factory()->for($owner)->create();
    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
    ]);

    Livewire::actingAs($owner)
        ->test('pages::teams.settings.members', ['team' => $team])
        ->call('revokeInvitation', $invitation->id)
        ->assertHasNoErrors();

    expect(TeamInvitation::find($invitation->id))->toBeNull();
});

it('prevents non-owners from managing team members', function () {
    $owner = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team = Team::factory()->for($owner)->create();
    $team->addMember($member);

    Livewire::actingAs($member)
        ->test('pages::teams.settings.members', ['team' => $team])
        ->assertForbidden();
});
