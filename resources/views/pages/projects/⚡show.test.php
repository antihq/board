<?php

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('creates a new task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->set('title', 'Test Task')
        ->call('createTask')
        ->assertHasNoErrors();

    expect($project->tasks()->count())->toBe(1);
    $task = $project->tasks()->first();
    expect($task->title)->toEqual('Test Task');
    expect($task->user_id)->toEqual($user->id);
    expect($task->team_id)->toEqual($team->id);
});
