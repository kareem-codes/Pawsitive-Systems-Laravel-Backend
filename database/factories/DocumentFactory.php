<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentable_type' => 'App\\Models\\Pet',
            'documentable_id' => Pet::factory(),
            'title' => fake()->sentence(3),
            'file_name' => fake()->word() . '.pdf',
            'file_path' => 'documents/Pet/' . fake()->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'document_type' => fake()->randomElement(['medical_report', 'lab_result', 'xray', 'prescription', 'vaccination_card', 'other']),
            'description' => fake()->optional()->paragraph(),
            'uploaded_by' => User::factory()->veterinarian(),
        ];
    }

    /**
     * Document for medical record
     */
    public function forMedicalRecord(): static
    {
        return $this->state(fn (array $attributes) => [
            'documentable_type' => 'App\\Models\\MedicalRecord',
            'document_type' => fake()->randomElement(['medical_report', 'lab_result', 'xray']),
        ]);
    }

    /**
     * Document for vaccination
     */
    public function forVaccination(): static
    {
        return $this->state(fn (array $attributes) => [
            'documentable_type' => 'App\\Models\\Vaccination',
            'document_type' => 'vaccination_card',
        ]);
    }
}
