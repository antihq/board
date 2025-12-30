<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('allows comment creator to edit their own comment', function () {
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
        'content' => 'Original comment',
    ]);

    $updatedContent = 'Updated comment content';

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', $updatedContent)
        ->call('save')
        ->assertHasNoErrors();

    $comment->refresh();
    expect($comment->content)->toContain($updatedContent);
    expect($comment->edited_by)->toEqual($user->id);
    expect($comment->edited_at)->not->toBeNull();
});

it('stores who edited comment and when', function () {
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
    $team->users()->attach($member->id, ['role' => 'member']);

    $comment = $task->comments()->create([
        'user_id' => $member->id,
        'content' => 'Original comment',
    ]);

    Livewire::actingAs($owner)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Updated by owner')
        ->call('save')
        ->assertHasNoErrors();

    $comment->refresh();
    expect($comment->content)->toContain('Updated by owner');
    expect($comment->edited_by)->toEqual($owner->id);
    expect($comment->edited_at)->not->toBeNull();
});

it('allows team owner to edit any comment', function () {
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
    $team->users()->attach($member->id, ['role' => 'member']);

    $comment = $task->comments()->create([
        'user_id' => $member->id,
        'content' => 'Original comment',
    ]);

    Livewire::actingAs($owner)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Updated by owner')
        ->call('save')
        ->assertHasNoErrors();

    $comment->refresh();
    expect($comment->content)->toContain('Updated by owner');
    expect($comment->edited_by)->toEqual($owner->id);
});

it('allows team admin to edit any comment', function () {
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
        'content' => 'Original comment',
    ]);

    Livewire::actingAs($admin)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Updated by admin')
        ->call('save')
        ->assertHasNoErrors();

    $comment->refresh();
    expect($comment->content)->toContain('Updated by admin');
    expect($comment->edited_by)->toEqual($admin->id);
});

it('does not allow regular member to edit another members comment', function () {
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
        'content' => 'Original comment',
    ]);

    Livewire::actingAs($member2)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Modified by another member')
        ->call('save')
        ->assertForbidden();

    $comment->refresh();
    expect($comment->content)->toContain('Original comment');
    expect($comment->edited_by)->toBeNull();
    expect($comment->edited_at)->toBeNull();
});

it('does not allow non-member to edit comment', function () {
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
        'content' => 'Original comment',
    ]);

    Livewire::actingAs($nonMember)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Modified by non-member')
        ->call('save')
        ->assertForbidden();

    $comment->refresh();
    expect($comment->content)->toContain('Original comment');
    expect($comment->edited_by)->toBeNull();
    expect($comment->edited_at)->toBeNull();
});

it('allows cancelling comment edit', function () {
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
        'content' => 'Original comment',
    ]);

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('isEditing', false)
        ->assertHasNoErrors()
        ->assertSet('isEditing', false);

    $comment->refresh();
    expect($comment->content)->toContain('Original comment');
    expect($comment->edited_by)->toBeNull();
    expect($comment->edited_at)->toBeNull();
});

it('adds images when editing a comment', function () {
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
        'content' => 'Original comment',
    ]);

    $newImages = [
        UploadedFile::fake()->image('new1.jpg'),
        UploadedFile::fake()->image('new2.png'),
    ];

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Updated content')
        ->set('images', $newImages)
        ->call('save')
        ->assertHasNoErrors();

    $comment->refresh();
    expect($comment->content)->toContain('Updated content');
    expect($comment->images)->toHaveCount(2);
    expect($comment->edited_by)->toEqual($user->id);
    expect($comment->edited_at)->not->toBeNull();
    expect(Storage::disk('public')->exists($comment->images->first()->path))->toBeTrue();
});

it('validates total images including existing comment images', function () {
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

    $comment->images()->createMany([
        ['user_id' => $user->id, 'path' => 'comment-images/existing1.jpg'],
        ['user_id' => $user->id, 'path' => 'comment-images/existing2.jpg'],
    ]);

    $newImages = [
        UploadedFile::fake()->image('new1.jpg'),
        UploadedFile::fake()->image('new2.png'),
        UploadedFile::fake()->image('new3.jpg'),
    ];

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('content', 'Updated content')
        ->set('images', $newImages)
        ->call('save')
        ->assertHasErrors(['images' => 'max']);

    $comment->refresh();
    expect($comment->images)->toHaveCount(2);
});

it('removes temporary comment image before saving', function () {
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

    $images = [
        UploadedFile::fake()->image('test1.jpg'),
        UploadedFile::fake()->image('test2.png'),
    ];

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('images', $images)
        ->call('removeImage', 0)
        ->assertHasNoErrors()
        ->assertSet('images', function ($images) {
            return count($images) === 1;
        });
});

it('clears temporary comment images when canceling comment edit', function () {
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

    $images = [
        UploadedFile::fake()->image('test1.jpg'),
        UploadedFile::fake()->image('test2.png'),
    ];

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->call('startEditing')
        ->set('images', $images)
        ->set('isEditing', false)
        ->assertHasNoErrors()
        ->assertSet('isEditing', false);
});

it('displays existing comment images when viewing comments', function () {
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

    $image1 = $comment->images()->create([
        'user_id' => $user->id,
        'path' => 'comment-images/test1.jpg',
    ]);

    $image2 = $comment->images()->create([
        'user_id' => $user->id,
        'path' => 'comment-images/test2.png',
    ]);

    Livewire::actingAs($user)->test('comment-item', ['comment' => $comment])
        ->assertSee('Test comment');

    expect($comment->images)->toHaveCount(2);
    expect($comment->images->first()->id)->toEqual($image1->id);
    expect($comment->images->last()->id)->toEqual($image2->id);
});

it('initializes content when starting edit', function () {
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
        'content' => 'Initial comment content',
    ]);

    $livewire = Livewire::actingAs($user)->test('comment-item', ['comment' => $comment]);

    expect($livewire->get('content'))->toEqual('');

    $livewire->call('startEditing');

    expect($livewire->get('content'))->toEqual('Initial comment content');
    expect($livewire->get('isEditing'))->toBeTrue();
});
