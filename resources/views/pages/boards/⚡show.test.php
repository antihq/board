<?php

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use Livewire\Livewire;

it('moves a column to a lower position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $column1 = Column::factory()->for($board)->create(['position' => 1]);
    $column2 = Column::factory()->for($board)->create(['position' => 2]);
    $column3 = Column::factory()->for($board)->create(['position' => 3]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('moveColumn', $column1->id, 3);

    expect($column1->fresh()->position)->toBe(3);
    expect($column2->fresh()->position)->toBe(1);
    expect($column3->fresh()->position)->toBe(2);
});

it('moves a column to a higher position', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $column1 = Column::factory()->for($board)->create(['position' => 1]);
    $column2 = Column::factory()->for($board)->create(['position' => 2]);
    $column3 = Column::factory()->for($board)->create(['position' => 3]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('moveColumn', $column3->id, 1);

    expect($column3->fresh()->position)->toBe(1);
    expect($column1->fresh()->position)->toBe(2);
    expect($column2->fresh()->position)->toBe(3);
});

it('moves column from position 2 to position 4 with 4 columns', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $column1 = Column::factory()->for($board)->create(['position' => 1]);
    $column2 = Column::factory()->for($board)->create(['position' => 2]);
    $column3 = Column::factory()->for($board)->create(['position' => 3]);
    $column4 = Column::factory()->for($board)->create(['position' => 4]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('moveColumn', $column2->id, 4);

    expect($column1->fresh()->position)->toBe(1);
    expect($column2->fresh()->position)->toBe(4);
    expect($column3->fresh()->position)->toBe(2);
    expect($column4->fresh()->position)->toBe(3);
});

it('moves column from position 4 to position 2 with 4 columns', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $board = Board::factory()->for($user->currentTeam)->create();

    $column1 = Column::factory()->for($board)->create(['position' => 1]);
    $column2 = Column::factory()->for($board)->create(['position' => 2]);
    $column3 = Column::factory()->for($board)->create(['position' => 3]);
    $column4 = Column::factory()->for($board)->create(['position' => 4]);

    Livewire::actingAs($user)
        ->test('pages::boards.show', ['board' => $board])
        ->call('moveColumn', $column4->id, 2);

    expect($column1->fresh()->position)->toBe(1);
    expect($column4->fresh()->position)->toBe(2);
    expect($column2->fresh()->position)->toBe(3);
    expect($column3->fresh()->position)->toBe(4);
});
