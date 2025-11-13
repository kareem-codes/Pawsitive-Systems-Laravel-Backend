<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $taxAmount = $subtotal * 0.1;
        $discountAmount = fake()->randomFloat(2, 0, 50);
        $total = $subtotal + $taxAmount - $discountAmount;
        $paidAmount = fake()->randomFloat(2, 0, $total);
        
        $status = 'pending';
        if ($paidAmount >= $total) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partially_paid';
        }
        
        return [
            'invoice_number' => 'INV-' . fake()->unique()->numerify('######'),
            'pet_id' => Pet::factory(),
            'user_id' => function (array $attributes) {
                $pet = Pet::find($attributes['pet_id']);
                return $pet ? $pet->user_id : User::factory()->owner();
            },
            'appointment_id' => null,
            'created_by' => User::factory()->cashier(),
            'invoice_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
