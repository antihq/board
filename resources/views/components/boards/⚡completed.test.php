<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Livewire\Livewire;

it('reorders a card within completed cards to a higher position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $card1 = Card::factory()->for($board)->for($user)->create(['position' => 0, 'completed_at' => now()->subMinutes(4)]);
    $card2 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()->subMinutes(3)]);
    $card3 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'completed_at' => now()->subMinutes(2)]);
    $card4 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'completed_at' => now()->subMinutes(1)]);

    Livewire::actingAs($user)
        ->test('boards.completed', ['board' => $board])
        ->call('moveCard', $card1->id, 2);

    expect($card1->fresh()->position)->toBe(2);
    expect($card2->fresh()->position)->toBe(0);
    expect($card3->fresh()->position)->toBe(1);
    expect($card4->fresh()->position)->toBe(3);
    expect($card1->fresh()->isCompleted())->toBeTrue();
    expect($card1->fresh()->column_id)->toBeNull();
    expect($card1->fresh()->postponed_at)->toBeNull();
});

it('reorders a card within completed cards to a lower position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $card1 = Card::factory()->for($board)->for($user)->create(['position' => 0, 'completed_at' => now()->subMinutes(4)]);
    $card2 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()->subMinutes(3)]);
    $card3 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'completed_at' => now()->subMinutes(2)]);
    $card4 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'completed_at' => now()->subMinutes(1)]);

    Livewire::actingAs($user)
        ->test('boards.completed', ['board' => $board])
        ->call('moveCard', $card3->id, 1);

    expect($card3->fresh()->position)->toBe(1);
    expect($card1->fresh()->position)->toBe(0);
    expect($card2->fresh()->position)->toBe(2);
    expect($card4->fresh()->position)->toBe(3);
    expect($card3->fresh()->isCompleted())->toBeTrue();
    expect($card3->fresh()->column_id)->toBeNull();
    expect($card3->fresh()->postponed_at)->toBeNull();
});

it('moves a card from a column to completed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create();

    $sourceCard1 = Card::factory()->for($board)->for($column)->for($user)->create(['position' => 1]);
    $sourceCard2 = Card::factory()->for($board)->for($column)->for($user)->create(['position' => 2]);
    $completedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()->subHour()]);

    Livewire::actingAs($user)
        ->test('boards.completed', ['board' => $board])
        ->call('moveCard', $sourceCard1->id, 2);

    expect($sourceCard1->fresh()->isCompleted())->toBeTrue();
    expect($sourceCard1->fresh()->column_id)->toBeNull();
    expect($sourceCard1->fresh()->position)->toBe(2);
    expect($sourceCard1->fresh()->postponed_at)->toBeNull();
    expect($sourceCard2->fresh()->position)->toBe(1);
    expect($completedCard1->fresh()->position)->toBe(1);
});

it('moves a card from opened state to completed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $openedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'column_id' => null]);
    $openedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'column_id' => null]);
    $completedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()->subHour()]);

    Livewire::actingAs($user)
        ->test('boards.completed', ['board' => $board])
        ->call('moveCard', $openedCard1->id, 2);

    expect($openedCard1->fresh()->isCompleted())->toBeTrue();
    expect($openedCard1->fresh()->column_id)->toBeNull();
    expect($openedCard1->fresh()->postponed_at)->toBeNull();
    expect($openedCard1->fresh()->position)->toBe(2);
    expect($openedCard2->fresh()->position)->toBe(1);
    expect($completedCard1->fresh()->position)->toBe(1);
});

it('moves a card from postponed to completed', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $postponedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'postponed_at' => now()->subHour()]);
    $postponedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'postponed_at' => now()->subHour()]);
    $completedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()->subHours(2)]);

    Livewire::actingAs($user)
        ->test('boards.completed', ['board' => $board])
        ->call('moveCard', $postponedCard1->id, 2);

    expect($postponedCard1->fresh()->isCompleted())->toBeTrue();
    expect($postponedCard1->fresh()->column_id)->toBeNull();
    expect($postponedCard1->fresh()->postponed_at)->toBeNull();
    expect($postponedCard1->fresh()->position)->toBe(2);
    expect($postponedCard2->fresh()->position)->toBe(1);
    expect($completedCard1->fresh()->position)->toBe(1);
});

it('moves a card from completed to a new position at top', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $completedCard1 = Card::factory()->for($board)->for($user)->create(['position' => 1, 'completed_at' => now()->subHours(2)]);
    $completedCard2 = Card::factory()->for($board)->for($user)->create(['position' => 2, 'completed_at' => now()->subHours(1)]);
    $completedCard3 = Card::factory()->for($board)->for($user)->create(['position' => 3, 'completed_at' => now()]);

    Livewire::actingAs($user)
        ->test('boards.completed', ['board' => $board])
        ->call('moveCard', $completedCard3->id, 1);

    expect($completedCard3->fresh()->position)->toBe(1);
    expect($completedCard1->fresh()->position)->toBe(2);
    expect($completedCard2->fresh()->position)->toBe(3);
    expect($completedCard3->fresh()->isCompleted())->toBeTrue();
    expect($completedCard3->fresh()->column_id)->toBeNull();
    expect($completedCard3->fresh()->postponed_at)->toBeNull();
});
