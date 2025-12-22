<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows team owner to edit auto-close settings', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'auto_close_days' => 30]);

    Livewire::actingAs($user)->test('pages::teams.settings.auto-close', ['team' => $team])
        ->set('auto_close_days', 90)
        ->call('save');

    $team->refresh();
    expect($team->auto_close_days)->toBe(90);
});

it('denies non-team owner from editing auto-close settings', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id, 'auto_close_days' => 30]);

    Livewire::actingAs($nonOwner)->test('pages::teams.settings.auto-close', ['team' => $team])
        ->assertForbidden();
});

it('validates auto-close days are in allowed options', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'auto_close_days' => 30]);

    Livewire::actingAs($user)->test('pages::teams.settings.auto-close', ['team' => $team])
        ->set('auto_close_days', 15) // Not in allowed options
        ->call('save')
        ->assertHasErrors(['auto_close_days' => 'in']);
});

it('requires auto-close days field', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'auto_close_days' => 30]);

    Livewire::actingAs($user)->test('pages::teams.settings.auto-close', ['team' => $team])
        ->set('auto_close_days', '')
        ->call('save')
        ->assertHasErrors(['auto_close_days' => 'required']);
});
