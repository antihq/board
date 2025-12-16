<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Livewire\Livewire;

it('moves a card within postponed cards to a lower position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $card1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);
    $card2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'postponed_at' => now()]);
    $card3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'postponed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.postponed', ['board' => $board])
        ->call('moveCard', $card1->id, 3);

    expect($card1->fresh()->position)->toBe(3);
    expect($card2->fresh()->position)->toBe(1);
    expect($card3->fresh()->position)->toBe(2);
    expect($card1->fresh()->isPostponed())->toBeTrue();
    expect($card1->fresh()->column_id)->toBeNull();
});

it('moves a card within postponed cards to a higher position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $card1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);
    $card2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'postponed_at' => now()]);
    $card3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'postponed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.postponed', ['board' => $board])
        ->call('moveCard', $card3->id, 1);

    expect($card3->fresh()->position)->toBe(1);
    expect($card1->fresh()->position)->toBe(2);
    expect($card2->fresh()->position)->toBe(3);
    expect($card3->fresh()->isPostponed())->toBeTrue();
    expect($card3->fresh()->column_id)->toBeNull();
});

it('moves a card from a column to postponed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();

    $sourceCard1 = Card::factory()->for($board)->for($column)->for($user)->create(['position' => 1]);
    $sourceCard2 = Card::factory()->for($board)->for($column)->for($user)->create(['position' => 2]);
    $postponedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.postponed', ['board' => $board])
        ->call('moveCard', $sourceCard1->id, 2);

    expect($sourceCard1->fresh()->isPostponed())->toBeTrue();
    expect($sourceCard1->fresh()->column_id)->toBeNull();
    expect($sourceCard1->fresh()->position)->toBe(2);
    expect($sourceCard2->fresh()->position)->toBe(1);
    expect($postponedCard1->fresh()->position)->toBe(1);
});

it('moves a card from opened state to postponed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $openedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $openedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);
    $postponedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.postponed', ['board' => $board])
        ->call('moveCard', $openedCard1->id, 2);

    expect($openedCard1->fresh()->isPostponed())->toBeTrue();
    expect($openedCard1->fresh()->column_id)->toBeNull();
    expect($openedCard1->fresh()->completed_at)->toBeNull();
    expect($openedCard1->fresh()->position)->toBe(2);
    expect($openedCard2->fresh()->position)->toBe(1);
    expect($postponedCard1->fresh()->position)->toBe(1);
});

it('moves a card from completed to postponed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $postponedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);
    $completedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()]);
    $completedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'completed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.postponed', ['board' => $board])
        ->call('moveCard', $completedCard1->id, 2);

    expect($completedCard1->fresh()->isPostponed())->toBeTrue();
    expect($completedCard1->fresh()->column_id)->toBeNull();
    expect($completedCard1->fresh()->completed_at)->toBeNull();
    expect($completedCard1->fresh()->position)->toBe(2);
    expect($postponedCard1->fresh()->position)->toBe(1);
    expect($completedCard2->fresh()->position)->toBe(1);
});

it('moves a card from postponed to a new position at top', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $postponedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()]);
    $postponedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'postponed_at' => now()]);
    $postponedCard3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'postponed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.postponed', ['board' => $board])
        ->call('moveCard', $postponedCard3->id, 1);

    expect($postponedCard3->fresh()->position)->toBe(1);
    expect($postponedCard1->fresh()->position)->toBe(2);
    expect($postponedCard2->fresh()->position)->toBe(3);
    expect($postponedCard3->fresh()->isPostponed())->toBeTrue();
    expect($postponedCard3->fresh()->column_id)->toBeNull();
});
