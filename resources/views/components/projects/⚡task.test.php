<?php

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Models\Section;
use Livewire\Livewire;

it('loads existing task tags in pillbox', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();
    $tag1 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);
    $tag2 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Feature']);

    $task->tags()->attach($tag1->id);

    $component = Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task]);

    expect($component->get('tags'))->toContain($tag1->id);
    expect($component->get('tags'))->not->toContain($tag2->id);
});

it('creates new tags when user types new tag name', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('tagTitle', 'New Feature')
        ->call('addTag');

    expect($task->fresh()->tags)->toHaveCount(1);
    expect($task->fresh()->tags->first()->name)->toEqual('New Feature');
    expect($task->fresh()->tags->first()->team_id)->toEqual($user->currentTeam->id);
    expect($task->fresh()->tags->first()->user_id)->toEqual($user->id);
});

it('attaches existing tags to tasks', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();
    $tag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('tags', [$tag->id])
        ->call('syncTags');

    expect($task->fresh()->tags)->toHaveCount(1);
    expect($task->fresh()->tags->first()->name)->toEqual('Bug');
});

it('handles mixed new and existing tags', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();
    $existingTag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('tags', [$existingTag->id])
        ->call('syncTags');

    expect($task->fresh()->tags)->toHaveCount(1);
    expect($task->fresh()->tags->pluck('name'))->toContain('Bug');
});

it('removes tags from tasks when deselected', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();
    $tag1 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);
    $tag2 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Feature']);

    $task->tags()->attach([$tag1->id, $tag2->id]);

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('tags', [$tag1->id])
        ->call('syncTags');

    expect($task->fresh()->tags)->toHaveCount(1);
    expect($task->fresh()->tags->first()->name)->toEqual('Bug');

    expect(Tag::find($tag2->id))->not->toBeNull();
});

it('does not create duplicate tags for same team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    $tag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('tagTitle', 'Bug')
        ->call('addTag')
        ->assertHasErrors('tagTitle');

    expect(Tag::where('name', 'Bug')->count())->toBe(1);
});

it('loads existing task assignees in pillbox', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();
    $user->currentTeam->members()->attach($member->id);

    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    $task->assignees()->attach($member->id);

    $component = Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task]);

    expect($component->get('assignedUserIds'))->toContain($member->id);
    expect($component->get('assignedUserIds'))->not->toContain($user->id);
});

it('assigns multiple team members to tasks', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $user->currentTeam->members()->attach([$member1->id, $member2->id]);

    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('assignedUserIds', [$member1->id, $member2->id])
        ->call('syncAssignedUsers');

    expect($task->fresh()->assignees)->toHaveCount(2);
    expect($task->fresh()->assignees->pluck('id'))->toContain($member1->id, $member2->id);
});

it('removes team members from tasks when deselected', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $user->currentTeam->members()->attach([$member1->id, $member2->id]);

    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    $task->assignees()->attach([$member1->id, $member2->id]);

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('assignedUserIds', [$member1->id])
        ->call('syncAssignedUsers');

    expect($task->fresh()->assignees)->toHaveCount(1);
    expect($task->fresh()->assignees->first()->id)->toBe($member1->id);
});

it('prevents assignment of users outside the team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $user->currentTeam->members()->attach($member->id);

    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('assignedUserIds', [$member->id, $outsider->id])
        ->call('syncAssignedUsers');

    expect($task->fresh()->assignees)->toHaveCount(1);
    expect($task->fresh()->assignees->first()->id)->toBe($member->id);
});

it('allows team owner to be assigned to tasks', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('assignedUserIds', [$user->id])
        ->call('syncAssignedUsers');

    expect($task->fresh()->assignees)->toHaveCount(1);
    expect($task->fresh()->assignees->first()->id)->toBe($user->id);
});

it('handles mixed assignment and removal of team members', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $member3 = User::factory()->create();
    $user->currentTeam->members()->attach([$member1->id, $member2->id, $member3->id]);

    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    $task->assignees()->attach([$member1->id, $member2->id]);

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('assignedUserIds', [$member1->id, $member3->id, $user->id])
        ->call('syncAssignedUsers');

    expect($task->fresh()->assignees)->toHaveCount(3);
    expect($task->fresh()->assignees->pluck('id'))->toContain($member1->id, $member3->id, $user->id);
    expect($task->fresh()->assignees->pluck('id'))->not->toContain($member2->id);
});

it('allows team members to add comments to tasks', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $section = Section::factory()->for($project)->create();
    $task = Task::factory()->for($project)->for($section)->for($user)->create();

    Livewire::actingAs($user)
        ->test('projects.task', ['task' => $task])
        ->set('commentBody', 'This is a test comment')
        ->call('addComment');

    expect($task->fresh()->comments)->toHaveCount(1);

    $comment = $task->fresh()->comments->first();
    expect($comment->comment_body)->toBe('This is a test comment');
    expect($comment->user->is($user))->toBeTrue();
});
