<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;

it('rejects request without valid signature', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = get("/device-login/{$user->id}");

    $response->assertStatus(403);
});

it('rejects request with expired signature', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $url = URL::signedRoute('auth.device-login', ['user' => $user->id], now()->subMinutes(31));

    $response = get($url);

    $response->assertStatus(403);
});

it('logs in user with valid signature', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $url = URL::signedRoute('auth.device-login', ['user' => $user->id]);

    $response = get($url);

    $response->assertRedirect(route('dashboard'));

    assertAuthenticated();
    expect(auth()->id())->toBe($user->id);
});

it('redirects to dashboard after successful login', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $url = URL::signedRoute('auth.device-login', ['user' => $user->id]);

    $response = get($url);

    $response->assertRedirect(route('dashboard'));
});

it('accepts signature within 30 minutes', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $url = URL::signedRoute('auth.device-login', ['user' => $user->id], now()->addMinutes(29));

    $response = get($url);

    $response->assertRedirect(route('dashboard'));
});

it('redirects to login with error for non-existent user', function () {
    $userId = 999999;

    $url = URL::signedRoute('auth.device-login', ['user' => $userId]);

    $response = get($url);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'The user account associated with this login link no longer exists.');
});
