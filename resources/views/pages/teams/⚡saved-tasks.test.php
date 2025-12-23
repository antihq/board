<?php

use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

it('renders successfully', function () {
    $user = User::factory()->create();
    $team = Team::factory()->for($user, 'owner')->create();

    Livewire::actingAs($user)
        ->test('pages::teams.saved-tasks', ['team' => $team])
        ->assertStatus(200);
});
