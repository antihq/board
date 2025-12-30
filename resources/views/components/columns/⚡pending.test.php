<?php

use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('creates a new task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    Livewire::actingAs($user)->test('columns.pending', ['project' => $project])
        ->set('title', 'Test Task')
        ->call('createTask')
        ->assertHasNoErrors();

    expect($project->tasks()->count())->toBe(1);
    $task = $project->tasks()->first();
    expect($task->title)->toEqual('Test Task');
    expect($task->user_id)->toEqual($user->id);
    expect($task->team_id)->toEqual($team->id);
    expect($task->number)->toEqual(1);
    expect($task->subscribers)->toHaveCount(1);
    expect($task->subscribers->first()->id)->toEqual($user->id);
});

it('reopens a completed task when moved to pending', function () {
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

    Livewire::actingAs($user)->test('columns.pending', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->reopened_at)->not->toBeNull();
    expect($task->reopened_by)->toEqual($user->id);
});

it('removes section assignment when task moved to pending', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);

    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'section_id' => 1,
        'section_moved_at' => now()->subDay(),
        'section_moved_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('columns.pending', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->toBeNull();
    expect($task->section_moved_by)->toBeNull();
});

it('removes section assignment and reopens completed task when moved to pending', function () {
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
        'section_id' => 1,
        'section_moved_at' => now()->subDay(),
        'section_moved_by' => $user->id,
    ]);

    Livewire::actingAs($user)->test('columns.pending', ['project' => $project])
        ->call('sortItem', $task->id, 0);

    $task->refresh();
    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->toBeNull();
    expect($task->section_moved_by)->toBeNull();
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->reopened_at)->not->toBeNull();
    expect($task->reopened_by)->toEqual($user->id);
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

    Livewire::actingAs($user)->test('columns.pending', ['project' => $project])
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

    Livewire::actingAs($user2)->test('columns.pending', ['project' => $project])
        ->call('deleteTask', $task->id)
        ->assertForbidden();

    expect(Task::find($task->id))->not->toBeNull();
});
