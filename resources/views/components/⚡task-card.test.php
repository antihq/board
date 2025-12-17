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

it('adds checklist items to task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->call('startAddingChecklistItem')
        ->set('newChecklistItemContent', 'First checklist item')
        ->call('saveChecklistItem')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->checklistItems)->toHaveCount(1);
    expect($task->checklistItems->first()->content)->toEqual('First checklist item');
    expect($task->checklistItems->first()->completed)->toBeFalse();
});

it('toggles checklist item completion', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    $checklistItem = $task->checklistItems()->create([
        'content' => 'Test item',
        'completed' => false,
    ]);

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->call('toggleChecklistItem', $checklistItem->id)
        ->assertHasNoErrors();

    $checklistItem->refresh();
    expect($checklistItem->completed)->toBeTrue();
});
