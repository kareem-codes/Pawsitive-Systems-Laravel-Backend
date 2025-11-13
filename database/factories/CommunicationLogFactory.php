<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunicationLog>
 */
class CommunicationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'staff_id' => \App\Models\User::factory(),
            'type' => $this->faker->randomElement(['call', 'email', 'sms', 'whatsapp', 'visit', 'other']),
            'subject' => $this->faker->sentence(),
            'notes' => $this->faker->paragraph(),
            'contacted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'duration_minutes' => $this->faker->optional()->numberBetween(5, 60),
        ];
    }
}
