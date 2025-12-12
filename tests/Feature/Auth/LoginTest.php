<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('renders the login screen', function () {
    $response = get('/login');

    $response->assertStatus(200);
});

it('logs out authenticated users', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post('/logout');

    $response->assertRedirect('/');

    assertGuest();
});
