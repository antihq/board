<?php

use App\Models\Comment;
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

it('validates comment content', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->call('addComment')
        ->assertHasErrors(['newComment' => 'required']);
});

it('orders comments from oldest to newest', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    // Create comments with different timestamps
    $firstComment = Comment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'content' => 'First comment',
        'created_at' => now()->subHours(3),
    ]);

    $secondComment = Comment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'content' => 'Second comment',
        'created_at' => now()->subHours(2),
    ]);

    $thirdComment = Comment::factory()->create([
        'task_id' => $task->id,
        'user_id' => $user->id,
        'content' => 'Third comment',
        'created_at' => now()->subHours(1),
    ]);

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->assertSet('comments.0.content', $firstComment->content)
        ->assertSet('comments.1.content', $secondComment->content)
        ->assertSet('comments.2.content', $thirdComment->content);
});
