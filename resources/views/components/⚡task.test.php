<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    $description = 'This is a test description for task.';

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('isEditingDescription', true)
        ->set('description', $description)
        ->call('saveDescription')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->description)->toContain($description);
});

it('saves task description with images successfully', function () {
    Storage::fake('public');

    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $description = 'This is a test description for task.';
    $images = [
        UploadedFile::fake()->image('test1.jpg'),
        UploadedFile::fake()->image('test2.png'),
    ];

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('isEditingDescription', true)
        ->set('description', $description)
        ->set('images', $images)
        ->call('saveDescription')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->description)->toContain($description);
    expect($task->images)->toHaveCount(2);
    expect($task->images->first()->user_id)->toEqual($user->id);
    expect(Storage::disk('public')->exists($task->images->first()->path))->toBeTrue();
});

it('validates maximum 4 images can be uploaded', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $images = [
        UploadedFile::fake()->image('test1.jpg'),
        UploadedFile::fake()->image('test2.png'),
        UploadedFile::fake()->image('test3.jpg'),
        UploadedFile::fake()->image('test4.png'),
        UploadedFile::fake()->image('test5.jpg'),
    ];

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('isEditingDescription', true)
        ->set('description', 'Test description')
        ->set('images', $images)
        ->call('saveDescription')
        ->assertHasErrors(['images' => 'max']);
});

it('validates total images including existing task images', function () {
    Storage::fake('public');

    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $task->images()->createMany([
        ['user_id' => $user->id, 'path' => 'task-images/existing1.jpg'],
        ['user_id' => $user->id, 'path' => 'task-images/existing2.jpg'],
    ]);

    $newImages = [
        UploadedFile::fake()->image('new1.jpg'),
        UploadedFile::fake()->image('new2.png'),
        UploadedFile::fake()->image('new3.jpg'),
    ];

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('isEditingDescription', true)
        ->set('description', 'Test description')
        ->set('images', $newImages)
        ->call('saveDescription')
        ->assertHasErrors(['images' => 'max']);

    $task->refresh();
    expect($task->images)->toHaveCount(2);
});

it('validates only image files can be uploaded', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $livewire = Livewire::actingAs($user)->test('task', ['task' => $task]);

    $livewire->set('isEditingDescription', true)
        ->set('description', 'Test description')
        ->call('saveDescription')
        ->assertHasNoErrors();

    $livewire2 = Livewire::actingAs($user)->test('task', ['task' => $task]);

    $livewire2->set('isEditingDescription', true)
        ->set('description', 'Test description')
        ->set('images', ['invalid'])
        ->call('saveDescription')
        ->assertHasErrors(['images.0']);
});

it('removes temporary image before saving description', function () {
    Storage::fake('public');

    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $images = [
        UploadedFile::fake()->image('test1.jpg'),
        UploadedFile::fake()->image('test2.png'),
    ];

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('isEditingDescription', true)
        ->set('images', $images)
        ->call('removeImage', 0)
        ->assertHasNoErrors()
        ->assertSet('images', function ($images) {
            return count($images) === 1;
        });
});

it('clears temporary images when canceling description edit', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $images = [
        UploadedFile::fake()->image('test1.jpg'),
        UploadedFile::fake()->image('test2.png'),
    ];

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('isEditingDescription', true)
        ->set('images', $images)
        ->set('isEditingDescription', false)
        ->set('images', [])
        ->assertHasNoErrors();
});

it('displays existing task images when viewing task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $image1 = $task->images()->create([
        'user_id' => $user->id,
        'path' => 'task-images/test1.jpg',
    ]);

    $image2 = $task->images()->create([
        'user_id' => $user->id,
        'path' => 'task-images/test2.png',
    ]);

    $livewire = Livewire::actingAs($user)->test('task', ['task' => $task]);

    $taskImages = $livewire->get('taskImages');

    expect($taskImages)->toHaveCount(2);
    expect($taskImages->first()->id)->toEqual($image1->id);
    expect($taskImages->last()->id)->toEqual($image2->id);
});

it('adds a comment successfully', function () {
    Notification::fake();

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
});

it('deletes checklist item successfully', function () {
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

    expect($task->checklistItems)->toHaveCount(1);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('deleteChecklistItem', $checklistItem->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->checklistItems)->toHaveCount(0);
});

it('removes deleted checklist item from completed items array', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $user->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $checklistItem1 = $task->checklistItems()->create(['content' => 'Item 1', 'completed' => true]);
    $checklistItem2 = $task->checklistItems()->create(['content' => 'Item 2', 'completed' => true]);

    $livewire = Livewire::actingAs($user)->test('task', ['task' => $task]);

    expect($livewire->get('completedChecklistItems'))->toContain($checklistItem1->id);
    expect($livewire->get('completedChecklistItems'))->toContain($checklistItem2->id);

    $livewire->call('deleteChecklistItem', $checklistItem1->id)
        ->assertHasNoErrors();

    expect($livewire->get('completedChecklistItems'))->not->toContain($checklistItem1->id);
    expect($livewire->get('completedChecklistItems'))->toContain($checklistItem2->id);

    $task->refresh();
    expect($task->checklistItems)->toHaveCount(1);
});

it('does not allow non-member to delete checklist item', function () {
    $owner = User::factory()->has(Team::factory())->create();
    $team = $owner->teams()->first();

    $member = User::factory()->create();
    $team->users()->attach($member->id, ['role' => 'member']);

    $nonMember = User::factory()->create();

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $owner->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $checklistItem = $task->checklistItems()->create([
        'content' => 'Test item',
        'completed' => false,
    ]);

    expect($task->checklistItems)->toHaveCount(1);

    Livewire::actingAs($nonMember)->test('task', ['task' => $task])
        ->call('deleteChecklistItem', $checklistItem->id)
        ->assertForbidden();

    $task->refresh();
    expect($task->checklistItems)->toHaveCount(1);
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
        ->set('isAddingChecklistItem', true)
        ->set('newChecklistItem', 'First checklist item')
        ->call('addChecklist')
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
        ->call('addTag')
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
    expect($task->section_moved_at)->toBeNull();
    expect($task->section_moved_by)->toBeNull();

    // Test that setting to null again does nothing (no-op)
    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('selectedSection', null)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->toBeNull();
});

it('closes a task successfully', function () {
    Notification::fake();

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
        ->call('close')
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->closed_at)->not->toBeNull();
    expect($task->closed_by)->toEqual($user->id);
    expect($task->reopened_at)->toBeNull();
    expect($task->reopened_by)->toBeNull();
    expect($task->section_id)->toEqual($section->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->section_moved_by)->toEqual($user->id);
});

it('reopens a task successfully', function () {
    Notification::fake();

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
        ->call('reopen')
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
    expect($task->subscribers)->toHaveCount(2);
    expect($task->subscribers->pluck('id')->toArray())->toEqual([$member1->id, $member2->id]);
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
    expect($task->subscribers)->toHaveCount(1);

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
    expect($task->subscribers)->toHaveCount(3);
});

it('can assign current user to task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Add to team members
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
    expect($task->subscribers)->toHaveCount(1);
    expect($task->subscribers->first()->id)->toEqual($user->id);
});

it('can assign team owner to task', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();

    // Add to team members
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
    expect($task->subscribers)->toHaveCount(1);
    expect($task->subscribers->first()->id)->toEqual($team->user_id);
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

it('notifies subscribers when task is closed', function () {
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

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('close')
        ->assertHasNoErrors();

    Notification::assertSentTo(
        [$subscriber1, $subscriber2],
        \App\Notifications\TaskClosed::class
    );
    Notification::assertNotSentTo($user, \App\Notifications\TaskClosed::class);
});

it('notifies subscribers when task is reopened', function () {
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

    $subscriber1 = User::factory()->create();
    $subscriber2 = User::factory()->create();
    $task->subscribers()->attach([$subscriber1->id, $subscriber2->id]);

    Notification::fake();

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('reopen')
        ->assertHasNoErrors();

    Notification::assertSentTo(
        [$subscriber1, $subscriber2],
        \App\Notifications\TaskReopened::class
    );
    Notification::assertNotSentTo($user, \App\Notifications\TaskReopened::class);
});

it('notifies subscribers when comment is added', function () {
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

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->set('newComment', 'Test comment')
        ->call('addComment')
        ->assertHasNoErrors();

    Notification::assertSentTo(
        [$subscriber1, $subscriber2],
        \App\Notifications\TaskCommented::class
    );
    Notification::assertNotSentTo($user, \App\Notifications\TaskCommented::class);
});

it('allows comment creator to delete their own comment', function () {
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

    expect($task->comments)->toHaveCount(1);

    Livewire::actingAs($user)->test('task', ['task' => $task])
        ->call('deleteComment', $comment->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->comments)->toHaveCount(0);
});

it('allows team owner to delete any comment', function () {
    $owner = User::factory()->has(Team::factory())->create();
    $team = $owner->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $owner->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $member = User::factory()->create();
    $comment = $task->comments()->create([
        'user_id' => $member->id,
        'content' => 'Test comment',
    ]);

    expect($task->comments)->toHaveCount(1);

    Livewire::actingAs($owner)->test('task', ['task' => $task])
        ->call('deleteComment', $comment->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->comments)->toHaveCount(0);
});

it('allows team admin to delete any comment', function () {
    $owner = User::factory()->has(Team::factory())->create();
    $team = $owner->teams()->first();
    $team->users()->attach($owner->id);

    $admin = User::factory()->create();
    $team->users()->attach($admin->id, ['role' => 'admin']);

    $member = User::factory()->create();
    $team->users()->attach($member->id, ['role' => 'member']);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $owner->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $comment = $task->comments()->create([
        'user_id' => $member->id,
        'content' => 'Test comment',
    ]);

    expect($task->comments)->toHaveCount(1);

    Livewire::actingAs($admin)->test('task', ['task' => $task])
        ->call('deleteComment', $comment->id)
        ->assertHasNoErrors();

    $task->refresh();
    expect($task->comments)->toHaveCount(0);
});

it('does not allow regular member to delete another members comment', function () {
    $owner = User::factory()->has(Team::factory())->create();
    $team = $owner->teams()->first();

    $member1 = User::factory()->create();
    $team->users()->attach($member1->id, ['role' => 'member']);

    $member2 = User::factory()->create();
    $team->users()->attach($member2->id, ['role' => 'member']);

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $owner->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $comment = $task->comments()->create([
        'user_id' => $member1->id,
        'content' => 'Test comment',
    ]);

    expect($task->comments)->toHaveCount(1);

    Livewire::actingAs($member2)->test('task', ['task' => $task])
        ->call('deleteComment', $comment->id)
        ->assertForbidden();

    $task->refresh();
    expect($task->comments)->toHaveCount(1);
});

it('does not allow non-member to delete comment', function () {
    $owner = User::factory()->has(Team::factory())->create();
    $team = $owner->teams()->first();

    $member = User::factory()->create();
    $team->users()->attach($member->id, ['role' => 'member']);

    $nonMember = User::factory()->create();

    $project = $team->projects()->create(['name' => 'Test Project', 'handle' => 'test-project']);
    $task = $project->tasks()->create([
        'title' => 'Test Task',
        'user_id' => $owner->id,
        'team_id' => $team->id,
        'number' => 1,
    ]);

    $comment = $task->comments()->create([
        'user_id' => $member->id,
        'content' => 'Test comment',
    ]);

    expect($task->comments)->toHaveCount(1);

    Livewire::actingAs($nonMember)->test('task', ['task' => $task])
        ->call('deleteComment', $comment->id)
        ->assertForbidden();

    $task->refresh();
    expect($task->comments)->toHaveCount(1);
});
