<?php

use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('saves task description successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $description = 'This is a test description for the task.';

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('editDescription')
        ->set('description', $description)
        ->call('saveDescription')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->description)->toContain($description);
});

it('adds a comment successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $commentContent = 'This is a test comment.';

    Livewire::actingAs($user)->test('task', ['task' => $task])
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
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    Livewire::actingAs($user)->test('task', ['task' => $task])
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
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $checklistItem = $task->checklistItems()->create([
        'content' => 'Test item',
        'completed' => false,
    ]);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('completedChecklistItems', [$checklistItem->id])
        ->assertHasNoErrors();

    $checklistItem->refresh();
    expect($checklistItem->completed)->toBeTrue();
});

it('creates a new tag successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $tagName = 'Bug Fix';

    Livewire::actingAs($user)->test('task', ['task' => $task])
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
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    // Create some existing tags for team
    $tag1 = $team->tags()->create(['name' => 'Bug Fix']);
    $tag2 = $team->tags()->create(['name' => 'Feature']);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedTags', [$tag1->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->tags)->toHaveCount(1);
    expect($task->tags->first()->id)->toEqual($tag1->id);

    // Update to include both tags
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedTags', [$tag1->id, $tag2->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->tags)->toHaveCount(2);
    expect($task->tags->pluck('id')->toArray())->toEqual([$tag1->id, $tag2->id]);
});

it('updates selected section successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    // Create some existing sections for project
    $section1 = $project->sections()->create(['title' => 'To Do', 'order' => 1]);
    $section2 = $project->sections()->create(['title' => 'In Progress', 'order' => 2]);

    // Test assigning to first section
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedSection', $section1->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toEqual($section1->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);

    // Test switching to second section
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedSection', $section2->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toEqual($section2->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);

    // Test removing section assignment
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedSection', null)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);
});

it('closes a task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $section = $project->sections()->create(['title' => 'In Progress', 'order' => 1]);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'section_id' => $section->id,
        'section_moved_at' => now()->subHour(),
        'section_moved_by' => $user->id,
    ]);

    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->section_id)->toEqual($section->id);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('closeTask')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
    expect($task->completed_by)->toEqual($user->id);
    expect($task->reopened_at)->toBeNull();
    expect($task->reopened_by)->toBeNull();
    expect($task->section_id)->toEqual($section->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);
});

it('reopens a task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $section = $project->sections()->create(['title' => 'In Progress', 'order' => 1]);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'section_id' => $section->id,
        'section_moved_at' => now()->subHour(),
        'section_moved_by' => $user->id,
        'completed_at' => now()->subMinutes(30),
        'completed_by' => $user->id,
    ]);

    expect($task->completed_at)->not->toBeNull();
    expect($task->completed_by)->toEqual($user->id);
    expect($task->reopened_at)->toBeNull();
    expect($task->reopened_by)->toBeNull();

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('reopenTask')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->reopened_at)->not->toBeNull();
    expect($task->reopened_by)->toEqual($user->id);
    expect($task->section_id)->toEqual($section->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);
});

it('toggles task priority from unprioritized to prioritized', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    expect($task->prioritized_at)->toBeNull();
    expect($task->prioritized_by)->toBeNull();

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('togglePriority')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->prioritized_at)->not->toBeNull();
    expect($task->prioritized_by)->toEqual($user->id);
    expect($task->prioritized_at->format('Y-m-d H:i'))->toEqual(now()->format('Y-m-d H:i'));
});

it('toggles task priority from prioritized to unprioritized', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
        'prioritized_at' => now()->subHour(),
        'prioritized_by' => $user->id,
    ]);

    expect($task->prioritized_at)->not->toBeNull();
    expect($task->prioritized_by)->toEqual($user->id);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('togglePriority')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->prioritized_at)->toBeNull();
    expect($task->prioritized_by)->toBeNull();
});

it('assigns team members to task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Create additional team members
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $team->users()->attach([$member1->id, $member2->id]);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    expect($task->assignees)->toHaveCount(0);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$member1->id, $member2->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(2);
    expect($task->assignees->pluck('id')->toArray())->toEqual([$member1->id, $member2->id]);
});

it('updates task assignees successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Create additional team members
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $member3 = User::factory()->create();
    $team->users()->attach([$member1->id, $member2->id, $member3->id]);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    // Initially assign member1
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$member1->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(1);
    expect($task->assignees->first()->id)->toEqual($member1->id);

    // Update to include member2 and remove member1
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$member2->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(1);
    expect($task->assignees->first()->id)->toEqual($member2->id);

    // Update to include multiple members
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$member1->id, $member2->id, $member3->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(3);
    expect($task->assignees->pluck('id')->toArray())->toEqual([$member1->id, $member2->id, $member3->id]);
});

it('can assign current user to task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Add the current user to the team members
    $team->users()->attach($user->id);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    expect($task->assignees)->toHaveCount(0);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$user->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(1);
    expect($task->assignees->first()->id)->toEqual($user->id);
});

it('can assign team owner to task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Add the team owner to the team members
    $team->users()->attach($team->user_id);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    expect($task->assignees)->toHaveCount(0);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$team->user_id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(1);
    expect($task->assignees->first()->id)->toEqual($team->user_id);
});

it('can remove all assignees from task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Create additional team members
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $team->users()->attach([$member1->id, $member2->id]);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    // Initially assign members
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [$member1->id, $member2->id])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(2);

    // Remove all assignments
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedAssignees', [])
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->assignees)->toHaveCount(0);
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

    // Create related resources
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

    // Verify all related resources exist
    expect($task->comments)->toHaveCount(1);
    expect($task->checklistItems)->toHaveCount(1);
    expect($task->tags)->toHaveCount(1);

    $livewire = Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('deleteTask')
        ->assertDispatched('task-deleted', taskId: $task->id);

    // Verify task and all related resources are deleted
    expect(Task::find($task->id))->toBeNull();
    expect(Comment::find($comment->id))->toBeNull();
    expect(ChecklistItem::find($checklistItem->id))->toBeNull();
    expect($tag->fresh())->not->toBeNull(); // Tag should still exist
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

    Livewire::actingAs($user2)->test('task', ['task' => $task])
        ->call('deleteTask')
        ->assertForbidden();

    // Verify task still exists
    expect(Task::find($task->id))->not->toBeNull();
});

it('subscribes to task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    expect($task->subscribers)->toHaveCount(0);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('toggleSubscribe')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->subscribers)->toHaveCount(1);
    expect($task->subscribers->first()->id)->toEqual($user->id);
});

it('unsubscribes from task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    // Subscribe first
    $task->subscribers()->attach($user->id);

    expect($task->subscribers)->toHaveCount(1);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('toggleSubscribe')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->subscribers)->toHaveCount(0);
});

it('prevents non-authorized users from subscribing to tasks', function () {
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

    Livewire::actingAs($user2)->test('task', ['task' => $task])
        ->call('toggleSubscribe')
        ->assertForbidden();

    // Verify no subscription was added
    expect($task->subscribers)->toHaveCount(0);
});

it('saves task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    expect($task->savers)->toHaveCount(0);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('toggleSaved')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->savers)->toHaveCount(1);
    expect($task->savers->first()->id)->toEqual($user->id);
});

it('unsaves task successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    // Save first
    $task->savers()->attach($user->id, ['team_id' => $team->id]);

    expect($task->savers)->toHaveCount(1);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('toggleSaved')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->savers)->toHaveCount(0);
});

it('stores team_id when saving task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('toggleSaved')
        ->assertHasNoErrors();

    $task->refresh();
    $savedTask = DB::table('saved_tasks')
        ->where('user_id', $user->id)
        ->where('task_id', $task->id)
        ->first();

    expect($savedTask)->not->toBeNull();
    expect($savedTask->team_id)->toEqual($team->id);
});
