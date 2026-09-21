<?php

namespace Database\Factories;

use App\Models\PracticeArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticeArea>
 */
class PracticeAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('PA???')),
            'name' => fake()->sentence(3),
        ];
    }
}
