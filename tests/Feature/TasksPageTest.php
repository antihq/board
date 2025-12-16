<?php

use App\Models\User;

test('tasks page requires authentication', function () {
    $response = $this->get('/tasks');

    $response->assertRedirect('/login');
});

test('authenticated user can view tasks page', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $response = $this->actingAs($user)->get('/tasks');

    $response->assertStatus(200);
});

test('tasks page displays correct title', function () {
    $user = User::factory()->withPersonalTeam()->create();

    $response = $this->actingAs($user)->get('/tasks');

    $response->assertSee('Tasks');
});
