<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeightRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'weight' => fake()->randomFloat(2, 1, 50),
            'unit' => fake()->randomElement(['kg', 'lb']),
            'measured_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'recorded_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
