<?php

use App\Models\Project;
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

it('allows team owner to create project through modal', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test('pages::teams.show', ['team' => $team])
        ->set('name', 'New Test Project')
        ->call('create')
        ->assertRedirect();

    expect(Project::where('name', 'New Test Project')->exists())->toBeTrue();
});

it('validates project name when creating', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)->test('pages::teams.show', ['team' => $team])
        ->set('name', '')
        ->call('create')
        ->assertHasErrors(['name' => 'required']);
});
