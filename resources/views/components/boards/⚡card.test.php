<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

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

    $tag = Tag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Bug']);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('tagTitle', 'Bug')
        ->call('addTag')
        ->assertHasErrors('tagTitle');

    expect(Tag::where('name', 'Bug')->count())->toBe(1);
});

it('loads existing card assignees in pillbox', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();
    $user->currentTeam->members()->attach($member->id);

    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    $card->assignees()->attach($member->id);

    $component = Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card]);

    expect($component->get('assignedUserIds'))->toContain($member->id);
    expect($component->get('assignedUserIds'))->not->toContain($user->id);
});

it('assigns multiple team members to cards', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $user->currentTeam->members()->attach([$member1->id, $member2->id]);

    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('assignedUserIds', [$member1->id, $member2->id])
        ->call('syncAssignedUsers');

    expect($card->fresh()->assignees)->toHaveCount(2);
    expect($card->fresh()->assignees->pluck('id'))->toContain($member1->id, $member2->id);
});

it('removes team members from cards when deselected', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $user->currentTeam->members()->attach([$member1->id, $member2->id]);

    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    $card->assignees()->attach([$member1->id, $member2->id]);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('assignedUserIds', [$member1->id])
        ->call('syncAssignedUsers');

    expect($card->fresh()->assignees)->toHaveCount(1);
    expect($card->fresh()->assignees->first()->id)->toBe($member1->id);
});

it('prevents assignment of users outside the team', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $user->currentTeam->members()->attach($member->id);

    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('assignedUserIds', [$member->id, $outsider->id])
        ->call('syncAssignedUsers');

    expect($card->fresh()->assignees)->toHaveCount(1);
    expect($card->fresh()->assignees->first()->id)->toBe($member->id);
});

it('allows team owner to be assigned to cards', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('assignedUserIds', [$user->id])
        ->call('syncAssignedUsers');

    expect($card->fresh()->assignees)->toHaveCount(1);
    expect($card->fresh()->assignees->first()->id)->toBe($user->id);
});

it('handles mixed assignment and removal of team members', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();
    $member3 = User::factory()->create();
    $user->currentTeam->members()->attach([$member1->id, $member2->id, $member3->id]);

    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    $card->assignees()->attach([$member1->id, $member2->id]);

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('assignedUserIds', [$member1->id, $member3->id, $user->id])
        ->call('syncAssignedUsers');

    expect($card->fresh()->assignees)->toHaveCount(3);
    expect($card->fresh()->assignees->pluck('id'))->toContain($member1->id, $member3->id, $user->id);
    expect($card->fresh()->assignees->pluck('id'))->not->toContain($member2->id);
});

it('allows team members to add comments to cards', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();
    $card = Card::factory()->for($column)->for($user)->create();

    Livewire::actingAs($user)
        ->test('boards.card', ['card' => $card])
        ->set('commentBody', 'This is a test comment')
        ->call('addComment');

    expect($card->fresh()->comments)->toHaveCount(1);

    $comment = $card->fresh()->comments->first();
    expect($comment->comment_body)->toBe('This is a test comment');
    expect($comment->user->is($user))->toBeTrue();
});
