<?php

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Livewire\Livewire;

it('moves a card within the same column to a lower position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create(['position' => 1]);

    $card1 = Card::factory()->for($column)->for($user)->create(['position' => 1]);
    $card2 = Card::factory()->for($column)->for($user)->create(['position' => 2]);
    $card3 = Card::factory()->for($column)->for($user)->create(['position' => 3]);

    Livewire::actingAs($user)
        ->test('boards.column', ['column' => $column])
        ->call('moveCard', $card1->id, 3);

    expect($card1->fresh()->position)->toBe(3);
    expect($card2->fresh()->position)->toBe(1);
    expect($card3->fresh()->position)->toBe(2);
});

it('moves a card within the same column to a higher position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $column = Column::factory()->for($board)->create(['position' => 1]);

    $card1 = Card::factory()->for($column)->for($user)->create(['position' => 1]);
    $card2 = Card::factory()->for($column)->for($user)->create(['position' => 2]);
    $card3 = Card::factory()->for($column)->for($user)->create(['position' => 3]);

    Livewire::actingAs($user)
        ->test('boards.column', ['column' => $column])
        ->call('moveCard', $card3->id, 1);

    expect($card3->fresh()->position)->toBe(1);
    expect($card1->fresh()->position)->toBe(2);
    expect($card2->fresh()->position)->toBe(3);
});

it('moves a card to a different column', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $sourceColumn = Column::factory()->for($board)->create(['position' => 1]);
    $targetColumn = Column::factory()->for($board)->create(['position' => 2]);

    $sourceCard1 = Card::factory()->for($sourceColumn)->for($user)->create(['position' => 1]);
    $sourceCard2 = Card::factory()->for($sourceColumn)->for($user)->create(['position' => 2]);
    $targetCard1 = Card::factory()->for($targetColumn)->for($user)->create(['position' => 1]);

    Livewire::actingAs($user)
        ->test('boards.column', ['column' => $targetColumn])
        ->call('moveCard', $sourceCard1->id, 2);

    expect($sourceCard1->fresh()->column_id)->toBe($targetColumn->id);
    expect($sourceCard1->fresh()->position)->toBe(2);
    expect($sourceCard2->fresh()->position)->toBe(1);
    expect($targetCard1->fresh()->position)->toBe(1);
});

it('moves a card to the top of a different column', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();
    $sourceColumn = Column::factory()->for($board)->create(['position' => 1]);
    $targetColumn = Column::factory()->for($board)->create(['position' => 2]);

    $sourceCard = Card::factory()->for($sourceColumn)->for($user)->create(['position' => 1]);
    $targetCard1 = Card::factory()->for($targetColumn)->for($user)->create(['position' => 1]);
    $targetCard2 = Card::factory()->for($targetColumn)->for($user)->create(['position' => 2]);

    Livewire::actingAs($user)
        ->test('boards.column', ['column' => $targetColumn])
        ->call('moveCard', $sourceCard->id, 1);

    expect($sourceCard->fresh()->column_id)->toBe($targetColumn->id);
    expect($sourceCard->fresh()->position)->toBe(1);
    expect($targetCard1->fresh()->position)->toBe(2);
    expect($targetCard2->fresh()->position)->toBe(3);
});
