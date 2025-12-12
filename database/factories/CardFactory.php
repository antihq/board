<?php

namespace Database\Factories;

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
            'title' => $this->faker->sentence(4),
            'description' => '<p>'.$this->faker->paragraph().'</p>',
            'position' => 1,
            'column_id' => function () {
                return \App\Models\Column::factory()->create()->id;
            },
            'user_id' => User::factory(),
        ];
    }
}
