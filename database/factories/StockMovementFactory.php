<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['in', 'out', 'adjustment', 'damaged', 'expired']);
        $quantityBefore = fake()->numberBetween(0, 100);
        $quantity = fake()->numberBetween(1, 50);
        
        $quantityAfter = match($type) {
            'in' => $quantityBefore + $quantity,
            'out', 'damaged', 'expired' => max(0, $quantityBefore - $quantity),
            'adjustment' => fake()->numberBetween(0, 150),
            default => $quantityBefore
        };
        
        return [
            'product_id' => Product::factory(),
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => null,
            'reference_id' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory()->admin(),
        ];
    }
    
    /**
     * Inbound stock movement
     */
    public function inbound(): static
    {
        return $this->state(function (array $attributes) {
            $quantityBefore = $attributes['quantity_before'];
            $quantity = $attributes['quantity'];
            
            return [
                'type' => 'in',
                'quantity_after' => $quantityBefore + $quantity,
                'notes' => 'Stock received from supplier'
            ];
        });
    }
    
    /**
     * Outbound stock movement
     */
    public function outbound(): static
    {
        return $this->state(function (array $attributes) {
            $quantityBefore = $attributes['quantity_before'];
            $quantity = min($attributes['quantity'], $quantityBefore);
            
            return [
                'type' => 'out',
                'quantity' => $quantity,
                'quantity_after' => $quantityBefore - $quantity,
                'notes' => 'Stock sold to customer'
            ];
        });
    }
    
    /**
     * Damaged stock movement
     */
    public function damaged(): static
    {
        return $this->state(function (array $attributes) {
            $quantityBefore = $attributes['quantity_before'];
            $quantity = min($attributes['quantity'], $quantityBefore);
            
            return [
                'type' => 'damaged',
                'quantity' => $quantity,
                'quantity_after' => $quantityBefore - $quantity,
                'notes' => 'Product damaged during handling'
            ];
        });
    }
    
    /**
     * Expired stock movement
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $quantityBefore = $attributes['quantity_before'];
            $quantity = min($attributes['quantity'], $quantityBefore);
            
            return [
                'type' => 'expired',
                'quantity' => $quantity,
                'quantity_after' => $quantityBefore - $quantity,
                'notes' => 'Product past expiration date'
            ];
        });
    }
}
