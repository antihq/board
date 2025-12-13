<?php

use App\Models\User;

test('cards page requires authentication', function () {
    $response = $this->get('/cards');

    $response->assertRedirect('/login');
});

test('authenticated user can view cards page', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $response = $this->actingAs($user)->get('/cards');

    $response->assertStatus(200);
});

test('cards page displays correct title', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $response = $this->actingAs($user)->get('/cards');

    $response->assertSee('Cards');
});
