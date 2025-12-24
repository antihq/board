<?php

use App\Models\Comment;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

it('allows comment creator to delete their own comment', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['team_id' => $team->id]);
    $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $user->id]);

    expect($user->can('delete', $comment))->toBeTrue();
});

it('allows team owner to delete any comment', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);
    $task = Task::factory()->create(['team_id' => $team->id]);
    $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $member->id]);

    expect($owner->can('delete', $comment))->toBeTrue();
});

it('allows team admin to delete any comment', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($admin->id, ['role' => 'admin']);
    $team->users()->attach($member->id, ['role' => 'member']);
    $task = Task::factory()->create(['team_id' => $team->id]);
    $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $member->id]);

    expect($admin->can('delete', $comment))->toBeTrue();
});

it('does not allow regular member to delete another members comment', function () {
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member1->id, ['role' => 'member']);
    $team->users()->attach($member2->id, ['role' => 'member']);
    $task = Task::factory()->create(['team_id' => $team->id]);
    $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $member1->id]);

    expect($member2->can('delete', $comment))->toBeFalse();
});

it('does not allow non-member to delete comment', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $nonMember = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $team->users()->attach($member->id, ['role' => 'member']);
    $task = Task::factory()->create(['team_id' => $team->id]);
    $comment = Comment::factory()->create(['task_id' => $task->id, 'user_id' => $member->id]);

    expect($nonMember->can('delete', $comment))->toBeFalse();
});
