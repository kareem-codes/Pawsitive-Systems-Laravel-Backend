<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['product', 'service', 'consultation']);
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 10, 200);
        $taxPercentage = 10.00; // 10% tax
        $taxAmount = ($unitPrice * $quantity * $taxPercentage) / 100;
        $discountAmount = fake()->randomFloat(2, 0, 10);
        $total = ($unitPrice * $quantity) + $taxAmount - $discountAmount;
        
        $itemNames = [
            'product' => ['Premium Dog Food', 'Cat Litter', 'Pet Shampoo', 'Flea Treatment', 'Dental Chews'],
            'service' => ['Grooming Service', 'Dental Cleaning', 'Nail Trimming', 'Bath & Brush', 'Ear Cleaning'],
            'consultation' => ['General Checkup', 'Emergency Consultation', 'Follow-up Visit', 'Wellness Exam', 'Senior Pet Checkup']
        ];
        
        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => $type === 'product' ? Product::factory() : null,
            'type' => $type,
            'item_name' => fake()->randomElement($itemNames[$type]),
            'description' => fake()->optional()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_percentage' => $taxPercentage,
            'tax_fixed' => null,
            'tax_amount' => $taxAmount,
            'discount_percentage' => null,
            'discount_amount' => $discountAmount,
            'total' => $total,
        ];
    }
    
    /**
     * Product type item
     */
    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
            'product_id' => Product::factory(),
        ]);
    }
    
    /**
     * Service type item
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
            'product_id' => null,
        ]);
    }
    
    /**
     * Consultation type item
     */
    public function consultation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'consultation',
            'product_id' => null,
        ]);
    }
}
