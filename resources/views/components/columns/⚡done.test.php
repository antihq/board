<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('completes an inbox task when moved to done', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    Livewire::actingAs($user)->test('columns.done', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
    expect($task->completed_by)->toEqual($user->id);
    expect($task->reopened_at)->toBeNull();
    expect($task->reopened_by)->toBeNull();
});

it('does not modify already completed task when moved to done', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    $completedAt = now()->subDay();
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'completed_at' => $completedAt,
        'completed_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('columns.done', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->completed_at)->toEqual($completedAt);
    expect($task->completed_by)->toEqual($user->id);
});
