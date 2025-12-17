<?php

use App\Models\Team;
use App\Models\User;
use App\Models\Comment;
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

it('saves task title successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Original Task Title',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    $newTitle = 'Updated Task Title';

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->call('editTitle')
        ->set('title', $newTitle)
        ->call('saveTitle')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->title)->toEqual($newTitle);
});

it('adds a comment successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    $commentContent = 'This is a test comment.';

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('newComment', $commentContent)
        ->call('addComment')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->comments)->toHaveCount(1);
    expect($task->comments->first()->content)->toContain($commentContent);
    expect($task->comments->first()->user_id)->toEqual($user->id);
});
