<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows guest to register and join team', function () {
    $team = Team::factory()->create();

    Livewire::test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->call('join')
        ->assertSee('Check your email');

    $user = User::firstWhere('email', 'john@example.com');
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('John Doe')
        ->and($user->joinedTeams()->where('teams.id', $team->id)->exists())->toBeFalse();
});

it('allows existing user to login and join team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    Livewire::test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->call('join')
        ->assertSee('Check your email');

    expect($user->joinedTeams()->where('teams.id', $team->id)->exists())->toBeFalse();
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
    $team = Team::factory()->create(['invitation_code_max_uses' => 1, 'invitation_code_uses_count' => 1]);

    Livewire::test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
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

it('creates personal team for new user', function () {
    $team = Team::factory()->create();

    Livewire::test('pages::teams.join', ['team' => $team, 'invitation_code' => $team->invitation_code])
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('join');

    $user = User::firstWhere('email', 'jane@example.com');
    expect($user->teams()->count())->toBe(1)
        ->and($user->teams()->first()->personal)->toBeTrue();
});
