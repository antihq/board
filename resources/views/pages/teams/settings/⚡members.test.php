<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows team owner to remove team member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($owner)->test('pages::teams.settings.members', ['team' => $team])
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

    Livewire::actingAs($admin)->test('pages::teams.settings.members', ['team' => $team])
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

    Livewire::actingAs($member1)->test('pages::teams.settings.members', ['team' => $team])
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

    Livewire::actingAs($admin)->test('pages::teams.settings.members', ['team' => $team])
        ->call('removeTeamMember', $owner->id);

    $team->refresh();
    expect($team->user_id)->toBe($originalOwnerId);
});

it('allows team member to leave team', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($member)->test('pages::teams.settings.members', ['team' => $team])
        ->call('leaveTeam');

    $team->refresh();
    expect($team->users()->where('users.id', $member->id)->exists())->toBeFalse();
});

it('allows team admin to leave team', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($admin->id, ['role' => 'admin']);

    Livewire::actingAs($admin)->test('pages::teams.settings.members', ['team' => $team])
        ->call('leaveTeam');

    $team->refresh();
    expect($team->users()->where('users.id', $admin->id)->exists())->toBeFalse();
});

it('does not allow team owner to leave team', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);

    Livewire::actingAs($owner)->test('pages::teams.settings.members', ['team' => $team])
        ->call('leaveTeam');

    $team->refresh();
    expect($team->user_id)->toBe($owner->id);
});

it('renders successfully', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test('pages::teams.settings.members', ['team' => $team])
        ->assertOk();
});

it('persists max uses changes to database', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'invitation_code_max_uses' => 10]);

    Livewire::actingAs($user)->test('pages::teams.settings.members', ['team' => $team])
        ->set('invitation_code_max_uses', 20)
        ->call('saveMaxUses');

    $team->refresh();
    expect($team->invitation_code_max_uses)->toBe(20);
});

it('validates max uses is at least 1', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'invitation_code_max_uses' => 10]);

    Livewire::actingAs($user)->test('pages::teams.settings.members', ['team' => $team])
        ->set('invitation_code_max_uses', 0)
        ->call('saveMaxUses')
        ->assertHasErrors(['invitation_code_max_uses' => 'min']);
});

it('requires max uses field', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'invitation_code_max_uses' => 10]);

    Livewire::actingAs($user)->test('pages::teams.settings.members', ['team' => $team])
        ->set('invitation_code_max_uses', '')
        ->call('saveMaxUses')
        ->assertHasErrors(['invitation_code_max_uses' => 'required']);
});

it('regenerates invitation code', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $oldCode = $team->invitation_code;

    Livewire::actingAs($user)->test('pages::teams.settings.members', ['team' => $team])
        ->call('regenerateInvitationCode');

    $team->refresh();
    expect($team->invitation_code)->not->toBe($oldCode);
});

it('resets usage count when regenerating invitation code', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'invitation_code_uses_count' => 5]);

    Livewire::actingAs($user)->test('pages::teams.settings.members', ['team' => $team])
        ->call('regenerateInvitationCode');

    $team->refresh();
    expect($team->invitation_code_uses_count)->toBe(0);
});
