<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'payment_number' => 'PAY-' . fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 10, 500),
            'payment_method' => fake()->randomElement(['cash', 'credit_card', 'debit_card', 'bank_transfer', 'other']),
            'payment_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'transaction_reference' => fake()->optional()->bothify('TXN-####-????'),
            'notes' => fake()->optional()->sentence(),
            'received_by' => User::factory()->cashier(),
        ];
    }
}
