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

it('moves closed task to section', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'completed_at' => now()->subDay(),
        'completed_by' => $user->id,
        'closed_at' => now()->subDay(),
        'closed_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->call('sortItem', $task->id, 0);

    $task->refresh();

    expect($task->section_id)->toBe($section->id);
    expect($task->section_moved_by)->toBe($user->id);
    expect($task->section_moved_at)->not->toBeNull();
    expect($task->closed_at)->toBeNull();
    expect($task->closed_by)->toBeNull();
});

it('updates section title', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id, 'title' => 'Old Title']);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->set('title', 'New Title')
        ->call('save');

    $section->refresh();

    expect($section->title)->toBe('New Title');
});

it('updates section color', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->set('color', '#ff0000')
        ->call('save');

    $section->refresh();

    expect($section->color)->toBe('#ff0000');
});

it('clears section color when empty', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id, 'color' => '#ff0000']);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->set('color', '')
        ->call('save');

    $section->refresh();

    expect($section->color)->toBeNull();
});

it('prevents non-owner from updating section', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $member = User::factory()->create();

    Livewire::actingAs($member)
        ->test('columns.section', ['section' => $section])
        ->set('title', 'New Title')
        ->call('save')
        ->assertForbidden();

    $section->refresh();

    expect($section->title)->not->toBe('New Title');
});

it('allows owner to delete section', function () {
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
        ->call('delete')
        ->assertDispatched('task.moved')
        ->assertDispatched('section.deleted');

    expect(Section::find($section->id))->toBeNull();

    $task->refresh();

    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->toBeNull();
    expect($task->section_moved_by)->toBeNull();
});

it('allows admin to delete section', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $admin = User::factory()->create();
    $team->users()->attach($admin->id, ['role' => 'admin']);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'section_id' => $section->id,
        'section_moved_at' => now(),
        'section_moved_by' => $owner->id,
    ]);

    Livewire::actingAs($admin)
        ->test('columns.section', ['section' => $section])
        ->call('delete')
        ->assertDispatched('task.moved')
        ->assertDispatched('section.deleted');

    expect(Section::find($section->id))->toBeNull();

    $task->refresh();

    expect($task->section_id)->toBeNull();
    expect($task->section_moved_at)->toBeNull();
    expect($task->section_moved_by)->toBeNull();
});

it('prevents non-admin from deleting section', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $member = User::factory()->create();

    Livewire::actingAs($member)
        ->test('columns.section', ['section' => $section])
        ->call('delete')
        ->assertForbidden();

    expect(Section::find($section->id))->not->toBeNull();
});

it('moves multiple tasks to pending when section is deleted', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $section = Section::factory()->create(['project_id' => $project->id]);
    $tasks = Task::factory()->count(3)->create([
        'project_id' => $project->id,
        'section_id' => $section->id,
        'section_moved_at' => now(),
        'section_moved_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test('columns.section', ['section' => $section])
        ->call('delete');

    expect(Section::find($section->id))->toBeNull();

    foreach ($tasks as $task) {
        $task->refresh();
        expect($task->section_id)->toBeNull();
        expect($task->section_moved_at)->toBeNull();
        expect($task->section_moved_by)->toBeNull();
    }
});
