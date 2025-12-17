<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('creates a new project successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    Livewire::actingAs($user)->test('projects-dropdown', ['team' => $team])
        ->set('name', 'Test Project')
        ->call('create')
        ->assertHasNoErrors();

    expect($team->projects()->count())->toBe(1);
    expect($team->projects()->first()->name)->toEqual('Test Project');
});
