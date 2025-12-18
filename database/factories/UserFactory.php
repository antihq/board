<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
                    'name' => $user->name.'\'s Team',
                ], $overrides))
                ->create();
        });
    }
}
