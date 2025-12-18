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
        ->set('completedChecklistItems', [$checklistItem->id])
        ->assertHasNoErrors();

    $checklistItem->refresh();
    expect($checklistItem->completed)->toBeTrue();
});

it('creates a new tag successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    $tagName = 'Bug Fix';

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('tagSearch', $tagName)
        ->call('createTag')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->tags)->toHaveCount(1);
    expect($task->tags->first()->name)->toEqual($tagName);
    expect($task->tags->first()->team_id)->toEqual($team->id);
});

it('updates selected tags successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    // Create some existing tags for team
    $tag1 = $team->tags()->create(['name' => 'Bug Fix']);
    $tag2 = $team->tags()->create(['name' => 'Feature']);

    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('selectedTags', [$tag1->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->tags)->toHaveCount(1);
    expect($task->tags->first()->id)->toEqual($tag1->id);

    // Update to include both tags
    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('selectedTags', [$tag1->id, $tag2->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->tags)->toHaveCount(2);
    expect($task->tags->pluck('id')->toArray())->toEqual([$tag1->id, $tag2->id]);
});

it('updates selected section successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
    ]);

    // Create some existing sections for project
    $section1 = $project->sections()->create(['title' => 'To Do', 'order' => 1]);
    $section2 = $project->sections()->create(['title' => 'In Progress', 'order' => 2]);

    // Test assigning to first section
    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('selectedSection', $section1->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toEqual($section1->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);

    // Test switching to second section
    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('selectedSection', $section2->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toEqual($section2->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);

    // Test removing section assignment
    Livewire::actingAs($user)->test('task-card', ['task' => $task])
        ->set('selectedSection', null)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);
});
