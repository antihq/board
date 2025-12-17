<?php

use App\Models\Section;
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
    // First section should have order 1 since max('order') returns 0 for empty table, then +1
    expect($section->order)->toBe(1);
});

it('creates multiple sections with correct order', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    $component = Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project]);

    // Create first section
    $component->set('title', 'First Section')
        ->call('createSection');

    // Create second section
    $component->set('title', 'Second Section')
        ->call('createSection');

    $sections = $project->sections()->ordered()->get();
    expect($sections)->toHaveCount(2);
    expect($sections[0]->title)->toBe('First Section');
    expect($sections[0]->order)->toBe(1);
    expect($sections[1]->title)->toBe('Second Section');
    expect($sections[1]->order)->toBe(2);
});

it('reorders sections correctly when moving forward', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    // Create three sections with 1-based indexing
    $section1 = $project->sections()->create(['title' => 'Section 1', 'order' => 1]);
    $section2 = $project->sections()->create(['title' => 'Section 2', 'order' => 2]);
    $section3 = $project->sections()->create(['title' => 'Section 3', 'order' => 3]);

    // Move section 1 to position 2 (after section 2)
    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->call('sortItem', $section1->id, 2);

    $sections = $project->sections()->ordered()->get();
    expect($sections[0]->title)->toBe('Section 2');
    expect($sections[0]->order)->toBe(1);
    expect($sections[1]->title)->toBe('Section 1');
    expect($sections[1]->order)->toBe(2);
    expect($sections[2]->title)->toBe('Section 3');
    expect($sections[2]->order)->toBe(3);
});

it('reorders sections correctly when moving backward', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    // Create three sections with 1-based indexing
    $section1 = $project->sections()->create(['title' => 'Section 1', 'order' => 1]);
    $section2 = $project->sections()->create(['title' => 'Section 2', 'order' => 2]);
    $section3 = $project->sections()->create(['title' => 'Section 3', 'order' => 3]);

    // Move section 3 to position 1 (before section 1)
    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->call('sortItem', $section3->id, 1);

    $sections = $project->sections()->ordered()->get();
    expect($sections[0]->title)->toBe('Section 3');
    expect($sections[0]->order)->toBe(1);
    expect($sections[1]->title)->toBe('Section 1');
    expect($sections[1]->order)->toBe(2);
    expect($sections[2]->title)->toBe('Section 2');
    expect($sections[2]->order)->toBe(3);
});

it('does not change order when moving to same position', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    $section = $project->sections()->create(['title' => 'Test Section', 'order' => 1]);
    $originalOrder = $section->order;

    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->call('sortItem', $section->id, 1);

    $section->refresh();
    expect($section->order)->toBe($originalOrder);
});

it('does not allow moving to position 0 (invalid)', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    $section1 = $project->sections()->create(['title' => 'Section 1', 'order' => 1]);
    $section2 = $project->sections()->create(['title' => 'Section 2', 'order' => 2]);

    $originalOrder = $section1->order;

    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->call('sortItem', $section1->id, 0); // Invalid position

    $section1->refresh();
    expect($section1->order)->toBe($originalOrder); // Should not change
});

it('does not allow moving to position greater than total sections', function () {
    $user = User::factory()->has(Team::factory())->create();
    $team = $user->teams()->first();
    $project = $team->projects()->create(['name' => 'Test Project']);

    $section1 = $project->sections()->create(['title' => 'Section 1', 'order' => 1]);
    $section2 = $project->sections()->create(['title' => 'Section 2', 'order' => 2]);

    $originalOrder = $section1->order;

    Livewire::actingAs($user)->test('pages::projects.show', ['team' => $team, 'project' => $project])
        ->call('sortItem', $section1->id, 5); // Invalid position (greater than total sections)

    $section1->refresh();
    expect($section1->order)->toBe($originalOrder); // Should not change
});