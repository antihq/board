<?php

use App\Models\Project;
use App\Models\Section;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->assertStatus(200);
});

it('displays tasks in section', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'section_id' => $section->id,
        'section_moved_at' => now(),
        'section_moved_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->assertSee($task->title);
});

it('moves task to section and removes completion', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'completed_at' => now(),
        'completed_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->call('sortItem', $task->id, 0);

    $task->refresh();

    expect($task->section_id)->toBe($section->id);
    expect($task->section_moved_by)->toBe($user->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->reopened_at)->not->toBeNull();
    expect($task->reopened_by)->toBe($user->id);
});

it('moves uncompleted task to section', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->call('sortItem', $task->id, 0);

    $task->refresh();

    expect($task->section_id)->toBe($section->id);
    expect($task->section_moved_by)->toBe($user->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->completed_at)->toBeNull();
    expect($task->completed_by)->toBeNull();
    expect($task->reopened_at)->toBeNull();
    expect($task->reopened_by)->toBeNull();
});

it('orders tasks by section moved time', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);

    $firstTask = Task::factory()->create([
        'project_id' => $project->id,
        'section_id' => $section->id,
        'section_moved_at' => now()->subMinutes(5),
        'section_moved_by' => $user->id,
    ]);

    $secondTask = Task::factory()->create([
        'project_id' => $project->id,
        'section_id' => $section->id,
        'section_moved_at' => now(),
        'section_moved_by' => $user->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section]);

    $tasks = $component->get('tasks');

    expect($tasks->first()->id)->toBe($secondTask->id);
    expect($tasks->last()->id)->toBe($firstTask->id);
});
