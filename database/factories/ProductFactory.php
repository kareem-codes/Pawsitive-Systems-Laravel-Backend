<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 5, 200);
        $cost = $price * 0.6; // 40% margin

        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('??###-####'),
            'description' => fake()->optional()->paragraph(),
            'category' => fake()->randomElement(['food', 'medicine', 'accessories', 'toys', 'grooming', 'other']),
            'price' => $price,
            'cost' => $cost,
            'quantity_in_stock' => fake()->numberBetween(0, 100),
            'reorder_threshold' => fake()->numberBetween(5, 20),
            'barcode' => fake()->optional()->ean13(),
            'expiry_date' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'tax_percentage' => fake()->optional()->randomFloat(2, 0, 20),
            'tax_fixed' => null,
            'is_active' => true,
            'image' => null,
        ];
    }
}
