<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
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
            'user_id' => function (array $attributes) {
                return Pet::find($attributes['pet_id'])->user_id;
            },
            'veterinarian_id' => User::factory()->veterinarian(),
            'appointment_date' => fake()->dateTimeBetween('now', '+30 days'),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'type' => fake()->randomElement(['checkup', 'surgery', 'vaccination', 'grooming', 'emergency', 'other']),
            'status' => fake()->randomElement(['pending', 'confirmed', 'completed', 'cancelled', 'no_show']),
            'reason' => fake()->sentence(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
