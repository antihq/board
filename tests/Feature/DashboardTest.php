<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('allows authenticated users to visit the dashboard', function () {
    $user = User::factory()->withPersonalTeamAndSubscription()->create();

    $response = actingAs($user)->get('/dashboard')->assertSuccessful();
});
