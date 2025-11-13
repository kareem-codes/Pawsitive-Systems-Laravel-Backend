<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vaccination>
 */
class VaccinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminDate = fake()->dateTimeBetween('-1 year', 'now');
        
        return [
            'pet_id' => Pet::factory(),
            'veterinarian_id' => User::factory()->veterinarian(),
            'medical_record_id' => null,
            'vaccine_name' => fake()->randomElement(['Rabies', 'DHPP', 'Bordetella', 'Leptospirosis', 'Feline Leukemia']),
            'administered_date' => $adminDate,
            'next_due_date' => fake()->dateTimeBetween($adminDate, '+2 years'),
            'batch_number' => fake()->bothify('BATCH-####-??'),
            'manufacturer' => fake()->optional()->company(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
