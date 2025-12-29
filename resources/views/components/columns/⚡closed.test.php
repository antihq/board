<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('closes a pending task when moved to closed', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    Livewire::actingAs($user)->test('columns.closed', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->closed_at)->not->toBeNull();
    expect($task->closed_by)->toEqual($user->id);
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
});

it('closes a completed task when moved to closed', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'completed_at' => now()->subDay(),
        'completed_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('columns.closed', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->closed_at)->not->toBeNull();
    expect($task->closed_by)->toEqual($user->id);
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
});

it('reopens a closed task when moved to pending', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'completed_at' => now()->subDay(),
        'completed_by' => $user->id,
        'closed_at' => now()->subDay(),
        'closed_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('columns.pending', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->closed_at)->toBeNull();
    expect($task->closed_by)->toBeNull();
    expect($task->completed_at)->toBeNull();
});
