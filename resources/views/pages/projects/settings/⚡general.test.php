<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows team owner to edit project name and handle', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    Livewire::actingAs($user)
        ->test('pages::projects.settings.general', ['team' => $team, 'project' => $project])
        ->set('name', 'Updated Project')
        ->set('handle', 'updated-project')
        ->call('save')
        ->assertRedirect();

    $project->refresh();
    expect($project->name)->toBe('Updated Project');
    expect($project->handle)->toBe('updated-project');
});

it('does not change handle when only name changes', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    Livewire::actingAs($user)
        ->test('pages::projects.settings.general', ['team' => $team, 'project' => $project])
        ->set('name', 'New Awesome Project')
        ->assertSet('handle', 'test-project'); // Handle should stay unchanged
});

it('auto-generates handle when creating new project', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    Livewire::actingAs($user)
        ->test('projects-dropdown', ['team' => $team])
        ->set('name', 'New Awesome Project')
        ->call('create')
        ->assertHasNoErrors();

    $project = $team->projects()->first();
    expect($project->handle)->toBe('new-awesome-project');
});

it('validates handle uniqueness during project edit', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project1 = $team->projects()->create(['name' => 'Project One', 'handle' => 'project-one']);
    $team->projects()->create(['name' => 'Project Two', 'handle' => 'project-two']);

    Livewire::actingAs($user)
        ->test('pages::projects.settings.general', ['team' => $team, 'project' => $project1])
        ->set('handle', 'project-two')
        ->call('save')
        ->assertHasErrors(['handle' => 'unique']);
});
