<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('allows project owner to edit auto-close settings', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'auto_close_days' => null]);

    Livewire::actingAs($user)->test('pages::projects.settings.auto-close', ['project' => $project])
        ->set('auto_close_days', 90)
        ->call('save');

    $project->refresh();
    expect($project->auto_close_days)->toBe(90);
});

it('allows project owner to set auto-close to null to use team default', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id, 'auto_close_days' => 30]);
    $project = Project::factory()->create(['team_id' => $team->id, 'auto_close_days' => 90]);

    Livewire::actingAs($user)->test('pages::projects.settings.auto-close', ['project' => $project])
        ->set('auto_close_days', '')
        ->call('save');

    $project->refresh();
    expect($project->auto_close_days)->toBeNull();
});

it('denies non-project owner from editing auto-close settings', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'auto_close_days' => null]);

    Livewire::actingAs($nonOwner)->test('pages::projects.settings.auto-close', ['project' => $project])
        ->assertForbidden();
});

it('validates auto-close days are in allowed options', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'auto_close_days' => null]);

    Livewire::actingAs($user)->test('pages::projects.settings.auto-close', ['project' => $project])
        ->set('auto_close_days', 15) // Not in allowed options
        ->call('save')
        ->assertHasErrors(['auto_close_days' => 'in']);
});
