<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Livewire\Livewire;

it('moves a card within opened cards to a lower position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $card1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $card2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);
    $card3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'column_id' => null]);

    Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->call('moveCard', $card1->id, 3);

    expect($card1->fresh()->position)->toBe(3);
    expect($card2->fresh()->position)->toBe(1);
    expect($card3->fresh()->position)->toBe(2);
    expect($card1->fresh()->column_id)->toBeNull();
    expect($card1->fresh()->postponed_at)->toBeNull();
    expect($card1->fresh()->completed_at)->toBeNull();
});

it('moves a card within opened cards to a higher position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $card1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $card2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);
    $card3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'column_id' => null]);

    Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->call('moveCard', $card3->id, 1);

    expect($card3->fresh()->position)->toBe(1);
    expect($card1->fresh()->position)->toBe(2);
    expect($card2->fresh()->position)->toBe(3);
    expect($card3->fresh()->column_id)->toBeNull();
    expect($card3->fresh()->postponed_at)->toBeNull();
    expect($card3->fresh()->completed_at)->toBeNull();
});

it('moves a card from a column to opened', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();

    $sourceCard1 = Card::factory()->for($board)->for($column)->for($user)->create(['position' => 1]);
    $sourceCard2 = Card::factory()->for($board)->for($column)->for($user)->create(['position' => 2]);
    $openedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);

    Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->call('moveCard', $sourceCard1->id, 2);

    expect($sourceCard1->fresh()->column_id)->toBeNull();
    expect($sourceCard1->fresh()->postponed_at)->toBeNull();
    expect($sourceCard1->fresh()->completed_at)->toBeNull();
    expect($sourceCard1->fresh()->position)->toBe(2);
    expect($sourceCard2->fresh()->position)->toBe(1);
    expect($openedCard1->fresh()->position)->toBe(1);
});

it('moves a card from postponed to opened', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $openedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $openedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);
    $postponedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);
    $postponedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'postponed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->call('moveCard', $postponedCard1->id, 2);

    expect($postponedCard1->fresh()->column_id)->toBeNull();
    expect($postponedCard1->fresh()->postponed_at)->toBeNull();
    expect($postponedCard1->fresh()->completed_at)->toBeNull();
    expect($postponedCard1->fresh()->position)->toBe(2);
    expect($openedCard1->fresh()->position)->toBe(1);
    expect($openedCard2->fresh()->position)->toBe(3);
    expect($postponedCard2->fresh()->position)->toBe(1);
});

it('moves a card from completed to opened', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $openedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $completedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()]);
    $completedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'completed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->call('moveCard', $completedCard1->id, 2);

    expect($completedCard1->fresh()->column_id)->toBeNull();
    expect($completedCard1->fresh()->postponed_at)->toBeNull();
    expect($completedCard1->fresh()->completed_at)->toBeNull();
    expect($completedCard1->fresh()->position)->toBe(2);
    expect($openedCard1->fresh()->position)->toBe(1);
    expect($completedCard2->fresh()->position)->toBe(1);
});

it('moves an opened card to a new position at top', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $openedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $openedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);
    $openedCard3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'column_id' => null]);

    Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->call('moveCard', $openedCard3->id, 1);

    expect($openedCard3->fresh()->position)->toBe(1);
    expect($openedCard1->fresh()->position)->toBe(2);
    expect($openedCard2->fresh()->position)->toBe(3);
    expect($openedCard3->fresh()->column_id)->toBeNull();
    expect($openedCard3->fresh()->postponed_at)->toBeNull();
    expect($openedCard3->fresh()->completed_at)->toBeNull();
});

it('adds a new card at the top and repositions existing cards', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $existingCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $existingCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);

    $livewire = Livewire::actingAs($user)
        ->test('boards.opened', ['board' => $board])
        ->set('title', 'New card at top')
        ->call('add');

    expect($existingCard1->fresh()->position)->toBe(2);
    expect($existingCard2->fresh()->position)->toBe(3);

    $newCard = $board->cards()->where('title', 'New card at top')->first();
    expect($newCard)->not->toBeNull();
    expect($newCard->position)->toBe(0);
    expect($newCard->column_id)->toBeNull();
    expect($newCard->postponed_at)->toBeNull();
    expect($newCard->completed_at)->toBeNull();
    expect($newCard->user_id)->toBe($user->id);
});
