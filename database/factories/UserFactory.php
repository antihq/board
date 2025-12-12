<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has a personal team and sets it as current.
     */
    public function withPersonalTeam(array $overrides = []): static
    {
        return $this->afterCreating(function ($user) use ($overrides) {
            Team::factory()
                ->state(array_merge([
                    'user_id' => $user->id,
                    'personal' => true,
                    'name' => $user->name,
                ], $overrides))
                ->create();
        });
    }

    /**
     * Indicate that the user has a personal team with an active subscription.
     */
    public function withPersonalTeamAndSubscription(array $teamOverrides = [], array $subOverrides = [], array $itemOverrides = []): static
    {
        return $this->afterCreating(function ($user) use ($teamOverrides, $subOverrides, $itemOverrides) {
            $team = Team::factory()
                ->state(array_merge([
                    'user_id' => $user->id,
                    'personal' => true,
                    'name' => $user->name,
                ], $teamOverrides))
                ->create();

            $subscription = Subscription::factory()
                ->state(array_merge([
                    'team_id' => $team->id,
                    'type' => 'default',
                    'stripe_id' => 'sub_'.Str::random(24),
                    'stripe_status' => 'active',
                ], $subOverrides))
                ->create();

            SubscriptionItem::factory()
                ->state(array_merge([
                    'subscription_id' => $subscription->id,
                    'stripe_price' => config('services.stripe.price_id'),
                    'quantity' => 1,
                ], $itemOverrides))
                ->create();
        });
    }
}
