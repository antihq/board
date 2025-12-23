<?php

use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    Livewire::actingAs($user)->test('pages::projects.settings.access', ['team' => $team, 'project' => $project])
        ->assertStatus(200);
});

it('allows project owner to enable access restriction', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'access_restricted' => false]);

    Livewire::actingAs($user)->test('pages::projects.settings.access', ['team' => $team, 'project' => $project])
        ->set('accessRestricted', true);

    $project->refresh();
    expect($project->access_restricted)->toBeTrue();
});

it('allows project owner to manage project members', function () {
    $user = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'access_restricted' => true]);

    $team->users()->attach($member->id, ['role' => 'member']);

    $component = Livewire::actingAs($user)->test('pages::projects.settings.access', ['team' => $team, 'project' => $project])
        ->set('selectedMembers', [$member->id]);

    $component->call('save');

    $project->refresh();
    expect($project->members->pluck('id')->toArray())->toContain($member->id);
    expect($project->members->pluck('id')->toArray())->toContain($user->id);
});

it('auto-adds team owner when access restriction is enabled', function () {
    $user = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $project = Project::factory()->create(['team_id' => $team->id, 'access_restricted' => false]);

    $team->users()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($user)->test('pages::projects.settings.access', ['team' => $team, 'project' => $project])
        ->set('accessRestricted', true)
        ->set('selectedMembers', [$member->id])
        ->call('save');

    $project->refresh();
    expect($project->members->pluck('id')->toArray())->toContain($member->id);
    expect($project->members->pluck('id')->toArray())->toContain($user->id);
});

it('denies non-project owner from editing access settings', function () {
    $owner = User::factory()->create();
    $nonOwner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $project = Project::factory()->create(['team_id' => $team->id]);

    Livewire::actingAs($nonOwner)->test('pages::projects.settings.access', ['team' => $team, 'project' => $project])
        ->assertForbidden();
});
