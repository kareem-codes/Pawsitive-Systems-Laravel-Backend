<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pet>
 */
class PetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $species = $this->faker->randomElement(['dog', 'cat', 'bird', 'rabbit']);
        
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->firstName(),
            'species' => $species,
            'breed' => $this->getBreed($species),
            'birth_date' => $this->faker->dateTimeBetween('-15 years', '-6 months'),
            'gender' => $this->faker->randomElement(['male', 'female', 'unknown']),
            'color' => $this->faker->safeColorName(),
            'weight' => $this->faker->randomFloat(2, 1, 100),
            'microchip_id' => $this->faker->optional(0.7)->numerify('###-###-###-###'),
            'allergies' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->paragraph(),
            'tags' => $this->faker->words(3),
        ];
    }

    private function getBreed(string $species): string
    {
        $breeds = [
            'dog' => ['Labrador', 'German Shepherd', 'Golden Retriever', 'Bulldog', 'Beagle'],
            'cat' => ['Persian', 'Siamese', 'Maine Coon', 'Bengal', 'British Shorthair'],
            'bird' => ['Parrot', 'Canary', 'Cockatiel', 'Parakeet', 'Finch'],
            'rabbit' => ['Dutch', 'Flemish Giant', 'Lionhead', 'Rex', 'Holland Lop'],
        ];

        return fake()->randomElement($breeds[$species] ?? ['Mixed']);
    }
}
