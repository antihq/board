<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('redirects unsubscribed users to the dashboard when accessing the billing portal', function () {
    /** @var User $user */
    $user = User::factory()->withPersonalTeamAndSubscription()->create();

    $response = actingAs($user)->get('/subscribe');

    $response->assertForbidden();
});
