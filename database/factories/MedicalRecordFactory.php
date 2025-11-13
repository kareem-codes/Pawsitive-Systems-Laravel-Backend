<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'veterinarian_id' => User::factory()->veterinarian(),
            'appointment_id' => null,
            'visit_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'weight' => fake()->randomFloat(2, 0.5, 50),
            'temperature' => fake()->randomFloat(1, 36.5, 40.0),
            'diagnosis' => fake()->sentence(),
            'treatment' => fake()->paragraph(),
            'prescriptions' => fake()->optional()->sentence(),
            'procedures' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->paragraph(),
            'next_visit_date' => fake()->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }
}
