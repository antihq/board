<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'user_id' => User::factory(),
            'title' => 'Test Card Title',
            'position' => 0,
        ];
    }

    public function postponed()
    {
        return $this->state(fn (array $attributes) => [
            'postponed_at' => now(),
            'column_id' => null,
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
            'column_id' => null,
        ]);
    }

    public function opened()
    {
        return $this->state(fn (array $attributes) => [
            'postponed_at' => null,
            'completed_at' => null,
            'column_id' => null,
        ]);
    }
}
