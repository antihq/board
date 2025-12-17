<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('creates a new section successfully', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->set('title', 'New Section')
        ->call('createSection');

    $section = $project->sections()->first();
    expect($section)->not->toBeNull();
    expect($section->title)->toBe('New Section');
    expect($section->project_id)->toBe($project->id);
});
