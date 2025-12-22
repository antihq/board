<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;

use function Pest\Laravel\artisan;

it('does not auto-close recently updated tasks', function () {
    $team = Team::factory()->create(['auto_close_days' => 30]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'team_id' => $team->id,
        'updated_at' => now()->subDays(5),
    ]);

    artisan('app:auto-close-tasks')
        ->assertExitCode(0);

    $task->refresh();
    expect($task->completed_at)->toBeNull();
    expect($task->closed_at)->toBeNull();
});

it('auto-closes tasks that are older than team default setting', function () {
    $team = Team::factory()->create(['auto_close_days' => 30]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'team_id' => $team->id,
        'updated_at' => now()->subDays(35),
    ]);

    artisan('app:auto-close-tasks')
        ->assertExitCode(0);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
    expect($task->closed_at)->not->toBeNull();
    expect($task->completed_at->toDateTimeString())->toEqual($task->closed_at->toDateTimeString());
});

it('auto-closes tasks that are older than project specific setting', function () {
    $team = Team::factory()->create(['auto_close_days' => 30]);
    $project = Project::factory()->create([
        'team_id' => $team->id,
        'auto_close_days' => 7,
    ]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'team_id' => $team->id,
        'updated_at' => now()->subDays(10),
    ]);

    artisan('app:auto-close-tasks')
        ->assertExitCode(0);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
    expect($task->closed_at)->not->toBeNull();
    expect($task->completed_at->toDateTimeString())->toEqual($task->closed_at->toDateTimeString());
});

it('auto-closes tasks when not in dry run mode', function () {
    $team = Team::factory()->create(['auto_close_days' => 30]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'team_id' => $team->id,
        'updated_at' => now()->subDays(35),
    ]);

    artisan('app:auto-close-tasks')
        ->assertExitCode(0);

    $task->refresh();
    expect($task->completed_at)->not->toBeNull();
    expect($task->closed_at)->not->toBeNull();
    expect($task->completed_at->toDateTimeString())->toEqual($task->closed_at->toDateTimeString());
});

it('does not auto-close already completed tasks', function () {
    $team = Team::factory()->create(['auto_close_days' => 30]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'team_id' => $team->id,
        'completed_at' => now()->subDays(35),
        'updated_at' => now()->subDays(40),
    ]);

    artisan('app:auto-close-tasks')
        ->assertExitCode(0);

    $task->refresh();
    expect($task->closed_at)->toBeNull();
});

it('does not auto-close already auto-closed tasks', function () {
    $team = Team::factory()->create(['auto_close_days' => 30]);
    $project = Project::factory()->create(['team_id' => $team->id]);
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'team_id' => $team->id,
        'closed_at' => now()->subDays(5),
        'completed_at' => now()->subDays(5),
        'updated_at' => now()->subDays(40),
    ]);

    artisan('app:auto-close-tasks')
        ->assertExitCode(0);

    $task->refresh();
    expect($task->closed_at)->not->toBeNull();
});
