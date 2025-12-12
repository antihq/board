<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('shows available team tags in pillbox', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();
    $tag1 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);
    $tag2 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Feature']);

    $otherTeam = Team::factory()->create();
    Tag::factory()->create(['team_id' => $otherTeam->id, 'name' => 'Other']);

    $component = Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card]);

    $teamTags = $component->get('teamTags');
    expect($teamTags)->toHaveCount(2);
    expect($teamTags->pluck('name'))->toContain('Bug', 'Feature');
    expect($teamTags->pluck('name'))->not->toContain('Other');
});

it('loads existing card tags in pillbox', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();
    $tag1 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);
    $tag2 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Feature']);

    $card->tags()->attach($tag1->id);

    $component = Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card]);

    expect($component->get('tags'))->toContain($tag1->id);
    expect($component->get('tags'))->not->toContain($tag2->id);
});

it('creates new tags when user types new tag name', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('tagTitle', 'New Feature')
        ->call('addTag');

    expect($card->fresh()->tags)->toHaveCount(1);
    expect($card->fresh()->tags->first()->name)->toEqual('New Feature');
    expect($card->fresh()->tags->first()->team_id)->toEqual($user->currentTeam->id);
    expect($card->fresh()->tags->first()->user_id)->toEqual($user->id);
});

it('attaches existing tags to cards', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();
    $tag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('tags', [$tag->id])
        ->call('syncTags');

    expect($card->fresh()->tags)->toHaveCount(1);
    expect($card->fresh()->tags->first()->name)->toEqual('Bug');
});

it('handles mixed new and existing tags', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();
    $existingTag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('tags', [$existingTag->id])
        ->call('syncTags');

    expect($card->fresh()->tags)->toHaveCount(1);
    expect($card->fresh()->tags->pluck('name'))->toContain('Bug');
});

it('removes tags from cards when deselected', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();
    $tag1 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);
    $tag2 = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Feature']);

    $card->tags()->attach([$tag1->id, $tag2->id]);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('tags', [$tag1->id])
        ->call('syncTags');

    expect($card->fresh()->tags)->toHaveCount(1);
    expect($card->fresh()->tags->first()->name)->toEqual('Bug');

    expect(Tag::find($tag2->id))->not->toBeNull();
});

it('does not create duplicate tags for same team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    // Create initial tag
    $tag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('tagTitle', 'Bug') // Try to create duplicate
        ->call('addTag')
        ->assertHasErrors('tagTitle'); // Should have validation error

    expect(Tag::where('name', 'Bug')->count())->toBe(1);
});
