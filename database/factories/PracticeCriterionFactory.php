<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\PracticeCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeCriterion>
 */
class PracticeCriterionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'code' => 'CR-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->sentence(8),
            'required' => true,
        ];
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'required' => false,
        ]);
    }
}
