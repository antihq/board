<?php

use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Task;
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

it('deletes task and all related resources successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'completed_at' => now(),
        'completed_by' => $user->id,
    ]);

    $comment = $task->comments()->create([
        'user_id' => $user->id,
        'content' => 'Test comment',
    ]);

    $checklistItem = $task->checklistItems()->create([
        'content' => 'Test checklist item',
        'completed' => false,
    ]);

    $tag = $team->tags()->create(['name' => 'Bug Fix']);
    $task->tags()->attach($tag->id);

    expect($task->comments)->toHaveCount(1);
    expect($task->checklistItems)->toHaveCount(1);
    expect($task->tags)->toHaveCount(1);

    Livewire::actingAs($user)->test('columns.completed', ['project' => $project])
        ->call('deleteTask', $task->id)
        ->assertDispatched('task-deleted', taskId: $task->id);

    expect(Task::find($task->id))->toBeNull();
    expect(Comment::find($comment->id))->toBeNull();
    expect(ChecklistItem::find($checklistItem->id))->toBeNull();
    expect($tag->fresh())->not->toBeNull();
});

it('prevents non-authorized users from deleting tasks', function () {
    $user1 = User::factory()->has(Team::factory())->create();
    $team1 = $user1->teams()->first();
    $project = $team1->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user1->id,
        'team_id' => $team1->id,
        'number' => 1,
    ]);

    $user2 = User::factory()->create();

    Livewire::actingAs($user2)->test('columns.completed', ['project' => $project])
        ->call('deleteTask', $task->id)
        ->assertForbidden();

    expect(Task::find($task->id))->not->toBeNull();
});
