<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('saves task description successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    $description = 'This is a test description for the task.';

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->call('editDescription')
        ->set('description', $description)
        ->call('saveDescription')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->description)->toEqual("<p>{$description}</p>");
});
