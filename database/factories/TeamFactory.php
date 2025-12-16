<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the team has an active subscription.
     */
    public function withSubscription(array $overrides = []): static
    {
        return $this->afterCreating(function ($team) use ($overrides) {
            $subscription = Subscription::factory()
                ->for($team, 'owner')
                ->state($overrides)
                ->create();

            SubscriptionItem::factory()
                ->for($subscription)
                ->state([
                    'stripe_price' => config('services.stripe.price_id'),
                    'quantity' => 1,
                ])
                ->create();
        });
    }
}
