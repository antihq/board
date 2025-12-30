<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('completes a pending task when moved to completed', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    Livewire::actingAs($user)->test('columns.completed', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
    expect($task->completed_by)->toEqual($user->id);
    expect($task->reopened_at)->toBeNull();
    expect($task->reopened_by)->toBeNull();
});

it('notifies subscribers when task is completed', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $subscriber1 = User::factory()->create();
    $subscriber2 = User::factory()->create();
    $task->subscribers()->attach([$subscriber1->id, $subscriber2->id]);

    Notification::fake();

    Livewire::actingAs($user)->test('columns.completed', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    Notification::assertSentTo(
        [$subscriber1, $subscriber2],
        \App\Notifications\TaskCompleted::class
    );
    Notification::assertNotSentTo($user, \App\Notifications\TaskCompleted::class);
});
