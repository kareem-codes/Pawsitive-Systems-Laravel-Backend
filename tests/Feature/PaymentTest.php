<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $cashier;
    protected Invoice $invoice;
    protected string $ownerToken;
    protected string $cashierToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->owner = User::factory()->owner()->create();
        $this->owner->assignRole('owner');
        $this->ownerToken = $this->owner->createToken('test-token')->plainTextToken;

        $this->cashier = User::factory()->cashier()->create();
        $this->cashier->assignRole('cashier');
        $this->cashierToken = $this->cashier->createToken('test-token')->plainTextToken;

        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->owner->id,
            'total_amount' => 100.00,
            'paid_amount' => 0.00,
            'status' => 'pending',
        ]);
    }

    public function test_cashier_can_create_payment(): void
    {
        $paymentData = [
            'invoice_id' => $this->invoice->id,
            'payment_number' => 'PAY-TEST-001',
            'amount' => 50.00,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
            'received_by' => $this->cashier->id,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/payments', $paymentData);

        $response->assertStatus(201)
            ->assertJson([
                'payment' => [
                    'invoice_id' => $this->invoice->id,
                    'amount' => '50.00',
                    'payment_method' => 'cash',
                ]
            ]);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'amount' => 50.00,
        ]);
    }

    public function test_payment_updates_invoice_status_to_partially_paid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_number' => 'PAY-TEST-002',
                'amount' => 50.00,
                'payment_method' => 'cash',
                'payment_date' => now()->format('Y-m-d'),
                'received_by' => $this->cashier->id,
            ]);

        $response->assertStatus(201);

        $this->invoice->refresh();
        $this->assertEquals(50.00, $this->invoice->paid_amount);
        $this->assertEquals('partially_paid', $this->invoice->status);
    }

    public function test_payment_updates_invoice_status_to_paid(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_number' => 'PAY-TEST-003',
                'amount' => 100.00,
                'payment_method' => 'credit_card',
                'payment_date' => now()->format('Y-m-d'),
                'received_by' => $this->cashier->id,
            ]);

        $response->assertStatus(201);

        $this->invoice->refresh();
        $this->assertEquals(100.00, $this->invoice->paid_amount);
        $this->assertEquals('paid', $this->invoice->status);
    }

    public function test_owner_can_view_their_payments(): void
    {
        Payment::factory()->count(3)->create(['invoice_id' => $this->invoice->id]);

        $otherInvoice = Invoice::factory()->create();
        Payment::factory()->count(2)->create(['invoice_id' => $otherInvoice->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/payments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_filter_payments_by_invoice(): void
    {
        Payment::factory()->count(2)->create(['invoice_id' => $this->invoice->id]);

        $otherInvoice = Invoice::factory()->create(['user_id' => $this->owner->id]);
        Payment::factory()->count(3)->create(['invoice_id' => $otherInvoice->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson("/api/v1/payments?invoice_id={$this->invoice->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_filter_payments_by_method(): void
    {
        Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'payment_method' => 'cash',
        ]);
        Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'payment_method' => 'credit_card',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/payments?payment_method=cash');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.payment_method', 'cash');
    }

    public function test_cashier_can_update_payment(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 50.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->putJson("/api/v1/payments/{$payment->id}", [
                'invoice_id' => $this->invoice->id,
                'payment_number' => $payment->payment_number,
                'amount' => 75.00,
                'payment_method' => 'credit_card',
                'payment_date' => $payment->payment_date,
                'received_by' => $payment->received_by,
            ]);

        $response->assertStatus(200)
            ->assertJson(['payment' => ['amount' => '75.00']]);
    }

    public function test_cashier_can_delete_payment(): void
    {
        $payment = Payment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'amount' => 50.00,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->deleteJson("/api/v1/payments/{$payment->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    }

    public function test_cashier_can_create_payment_with_amount_exceeding_balance(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/payments', [
                'invoice_id' => $this->invoice->id,
                'payment_number' => 'PMT-' . rand(1000, 9999),
                'received_by' => $this->cashier->id,
                'amount' => 150.00,
                'payment_method' => 'cash',
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(201)
            ->assertJson(['payment' => ['amount' => '150.00']]);
    }

    public function test_validation_fails_for_invalid_payment_method(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->cashierToken)
            ->postJson('/api/v1/payments', [
                'invoice_id' => $this->invoice->id,
                'amount' => 50.00,
                'payment_method' => 'invalid_method',
                'payment_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }
}
