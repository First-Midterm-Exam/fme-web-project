<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\PracticeArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Practice>
 */
class PracticeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_area_id' => PracticeArea::factory(),
            'code' => strtoupper(fake()->unique()->lexify('PR???')).' '.fake()->numberBetween(1, 3).'.'.fake()->numberBetween(1, 9),
            'name' => fake()->sentence(4),
            'level' => fake()->numberBetween(1, 3),
        ];
    }
}
